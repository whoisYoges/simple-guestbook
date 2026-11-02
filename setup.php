<?php
// Connects to the database and does first-run setup. All settings come from config.php;
// anything an older config.php doesn't define falls back to a default here.

$title = $title ?? 'Guestbook';
$per_page = max(1, (int)($per_page ?? 10));
$timezone = $timezone ?? 'UTC';
date_default_timezone_set($timezone);

$hostname = $hostname ?? 'localhost';
$port = (int)($port ?? 3306);
$tablename = $tablename ?? 'guestbook';

// Older config.php files open the connection themselves
if (!isset($dbconnect)) {
    $dbconnect = new mysqli($hostname, $hostuser, $hostpass, $dbname, $port);
}

// Look up the table; create it on first run
$tableQuery = $dbconnect->prepare("SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
$tableQuery->bind_param('ss', $dbname, $tablename);
$tableQuery->execute();
$tableQuery->bind_result($tableCollation);
if (!$tableQuery->fetch()) {
    $tableCollation = null;
}
$tableQuery->close();

if ($tableCollation === null) {
    $dbconnect->query("CREATE TABLE IF NOT EXISTS `$dbname`.`$tablename` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(60) NOT NULL,
        `message` TEXT NOT NULL,
        `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $tableCollation = 'utf8mb4_unicode_ci';
}

// Talk to the table in the charset it stores. Old latin1 tables were written over the server's
// default connection charset, so keep that for them (until migrations/2026-11-02-utf8mb4.php).
// config.php can force a charset with $dbcharset.
if (!isset($dbcharset)) {
    $dbcharset = strpos($tableCollation, 'utf8mb4') === 0 ? 'utf8mb4' : '';
}
if ($dbcharset !== '') {
    $dbconnect->set_charset($dbcharset);
}
