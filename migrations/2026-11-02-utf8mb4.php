<?php
// One-off migration: move the guestbook table from latin1 to utf8mb4 and repair old entries.
//
// What it fixes:
//  1. Charset: the latin1 table actually holds UTF-8 bytes (written over a latin1 connection).
//     The bytes are copied as-is into a utf8mb4 table, so nothing is converted twice.
//     A few very old rows hold real Windows-1252 bytes; those are converted to UTF-8.
//  2. Double escaping: messages posted after the "minimal security" change (live from early 2025)
//     were SQL-escaped and then HTML-encoded twice, e.g. don't -> don\&amp;#039;t, line breaks
//     -> literal \r\n. Those are restored to what the visitor typed, then encoded once
//     (the format the current code stores).
//
// Safety: builds a new table, swaps it in with one atomic RENAME, and keeps the original as
// <table>__latin1_backup. Rollback:
//   RENAME TABLE <table> TO <table>__utf8mb4_failed, <table>__latin1_backup TO <table>;
//
// Usage (from the project root, after a backup/dump):
//   php migrations/2026-11-02-utf8mb4.php                 dry run, shows every change
//   php migrations/2026-11-02-utf8mb4.php --apply         do it
// Options: --old-escaping-since="2025-01-01 00:00:00"   first date of double-escaped posts
// Nothing to change afterwards: setup.php sees the utf8mb4 table and switches the connection to
// utf8mb4 by itself (unless config.php forces another charset with $dbcharset).

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$options = getopt('', ['apply', 'old-escaping-since:']);
$apply = isset($options['apply']);
// Last correctly stored post in the prod data is 2024-12-08, first double-escaped one 2025-02-11
$oldEscapingSince = $options['old-escaping-since'] ?? '2025-01-01 00:00:00';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
include __DIR__ . '/../config.php';
include __DIR__ . '/../setup.php';
$dbconnect->set_charset('utf8mb4');
$dbconnect->query("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'");

$table = $tablename;
$newTable = $table . '__utf8mb4';
$backupTable = $table . '__latin1_backup';

function fail($msg) {
    fwrite(STDERR, "ERROR: $msg\n");
    exit(1);
}

// Stored bytes -> valid UTF-8
function toUtf8($bytes) {
    if (preg_match('//u', $bytes)) {
        return $bytes;
    }
    // Cut off mid-character by the old 60-byte column limit: drop the partial character
    $trimmed = preg_replace('/[\xC0-\xFF][\x80-\xBF]*$/', '', $bytes);
    if ($trimmed !== $bytes && preg_match('//u', $trimmed)) {
        return $trimmed;
    }
    // Real Windows-1252 text (MariaDB's "latin1" is Windows-1252)
    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($bytes, 'UTF-8', 'Windows-1252');
    }
    if (function_exists('iconv')) {
        return iconv('Windows-1252', 'UTF-8//IGNORE', $bytes);
    }
    fail('need the mbstring or iconv extension to convert Windows-1252 rows');
}

// Undo htmlspecialchars(htmlspecialchars(mysqli_escape_string($text))) and encode once
function undoOldEscaping($text) {
    $text = htmlspecialchars_decode(htmlspecialchars_decode($text, ENT_QUOTES), ENT_QUOTES);
    $text = preg_replace_callback('/\\\\(.)/s', function ($m) {
        return ['0' => '', 'n' => "\n", 'r' => "\r", 'Z' => ''][$m[1]] ?? $m[1];
    }, $text);
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Same normalisation the current code applies to new posts
function normalise($text) {
    $text = str_replace("\r\n", "\n", $text);
    $trimmed = trim($text);
    return $trimmed === '' ? $text : $trimmed;
}

// What visitors see, for the before/after listing
function shown($text) {
    $text = str_replace("\n", '⏎', htmlspecialchars_decode($text, ENT_QUOTES));
    return preg_match_all('/./us', $text) > 160 ? preg_replace('/^(.{160}).*$/us', '$1…', $text) : $text;
}

// --- Preflight -----------------------------------------------------------------------------

$check = $dbconnect->prepare("SELECT TABLE_NAME, TABLE_COLLATION, AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN (?, ?, ?)");
$check->bind_param('ssss', $dbname, $table, $newTable, $backupTable);
$check->execute();
$check->bind_result($foundName, $foundCollation, $foundAutoIncrement);
$tables = [];
while ($check->fetch()) {
    $tables[$foundName] = ['collation' => $foundCollation, 'auto_increment' => $foundAutoIncrement];
}
$check->close();

if (!isset($tables[$table])) {
    fail("table `$dbname`.`$table` not found (check \$dbname / \$tablename in config.php)");
}
if (strpos($tables[$table]['collation'], 'latin1') !== 0) {
    fail("`$table` is {$tables[$table]['collation']}, not latin1 - already migrated?");
}
if (isset($tables[$backupTable])) {
    fail("`$backupTable` already exists - a previous run finished; remove it only once you're sure");
}
$autoIncrement = (int)$tables[$table]['auto_increment'];

// Modern Unicode collation on MariaDB 10.10+, older servers fall back to unicode_ci
try {
    $dbconnect->query("SELECT _utf8mb4 'a' COLLATE utf8mb4_uca1400_ai_ci");
    $collation = 'utf8mb4_uca1400_ai_ci';
} catch (mysqli_sql_exception $e) {
    $collation = 'utf8mb4_unicode_ci';
}

// --- Compute new values --------------------------------------------------------------------

$result = $dbconnect->query("SELECT id, CAST(name AS BINARY) AS name, CAST(message AS BINARY) AS message, date FROM `$dbname`.`$table` ORDER BY id");
$fixed = [];
$stats = ['changed' => 0, 'old_escaping' => 0, 'windows1252' => 0];

while ($row = $result->fetch_assoc()) {
    $oldEra = $row['date'] >= $oldEscapingSince;
    $new = [];
    foreach (['name', 'message'] as $col) {
        if (!preg_match('//u', $row[$col]) && preg_replace('/[\xC0-\xFF][\x80-\xBF]*$/', '', $row[$col]) === $row[$col]) {
            $stats['windows1252']++;
        }
        $value = toUtf8($row[$col]);
        $new[$col] = normalise($oldEra ? undoOldEscaping($value) : $value);
    }
    $stats['old_escaping'] += $oldEra ? 1 : 0;

    $nameLength = preg_match_all('/./us', $new['name']);
    if ($nameLength > 60) {
        fail("row {$row['id']}: name would be $nameLength characters (column holds 60)");
    }

    if ($new['name'] !== $row['name'] || $new['message'] !== $row['message']) {
        $stats['changed']++;
        printf("#%d %s\n  before: %s | %s\n  after:  %s | %s\n", $row['id'], $row['date'],
            shown(toUtf8($row['name'])), shown(toUtf8($row['message'])), shown($new['name']), shown($new['message']));
    }
    $fixed[] = [$row['id'], $new['name'], $new['message'], $row['date']];
}

printf("\n%d rows, %d changed (%d in the double-escaped era, %d Windows-1252 fields). Collation: %s\n",
    count($fixed), $stats['changed'], $stats['old_escaping'], $stats['windows1252'], $collation);

if (!$apply) {
    echo "Dry run only, nothing changed. Re-run with --apply to migrate.\n";
    exit(0);
}

// --- Apply ---------------------------------------------------------------------------------

$dbconnect->query("DROP TABLE IF EXISTS `$dbname`.`$newTable`");
$dbconnect->query("CREATE TABLE `$dbname`.`$newTable` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(60) NOT NULL,
    `message` TEXT NOT NULL,
    `date` DATETIME NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$collation AUTO_INCREMENT=$autoIncrement");

$dbconnect->begin_transaction();
$insert = $dbconnect->prepare("INSERT INTO `$dbname`.`$newTable` (`id`, `name`, `message`, `date`) VALUES (?, ?, ?, ?)");
foreach ($fixed as [$id, $name, $message, $date]) {
    $insert->bind_param('isss', $id, $name, $message, $date);
    $insert->execute();
}
$dbconnect->commit();

// Abort if someone posted while we were copying
$live = (int)$dbconnect->query("SELECT COUNT(*) FROM `$dbname`.`$table`")->fetch_row()[0];
$copied = (int)$dbconnect->query("SELECT COUNT(*) FROM `$dbname`.`$newTable`")->fetch_row()[0];
if ($live !== count($fixed) || $copied !== count($fixed)) {
    fail("row count changed during migration ($live live, $copied copied) - nothing swapped, re-run");
}

$dbconnect->query("RENAME TABLE `$dbname`.`$table` TO `$dbname`.`$backupTable`, `$dbname`.`$newTable` TO `$dbname`.`$table`");

echo "Done. `$table` is now utf8mb4/InnoDB; the original is kept as `$backupTable`.\n";
echo "The site now uses utf8mb4 automatically; nothing to change in config.php.\n";
