<?php
// Session cookie: not readable by JS, not sent cross-site, HTTPS-only when served over HTTPS
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

// Never show internal errors (DB credentials, paths, queries) to visitors; log them instead
ini_set('display_errors', '0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_exception_handler(function ($e) {
    error_log((string)$e);
    http_response_code(500);
    exit('Something went wrong. Please try again later.');
});

// Get a POST field as a trimmed string; non-string input (e.g. name[]=x) becomes ''
function postField($key){
    return (isset($_POST[$key]) && is_string($_POST[$key])) ? trim($_POST[$key]) : '';
}

// Max lengths in characters; name matches the VARCHAR(60) column
$maxNameLength = 60;
$maxMessageLength = 600;

// Anti-spam limits
$minFormSeconds = 3;        // a human needs at least this long to fill in the form
$sessionCooldown = 30;      // seconds between posts from the same visitor
$floodWindowMinutes = 10;   // site-wide: at most $floodMaxMessages per window
$floodMaxMessages = 10;

// True if the text has at least one character that actually renders: ignores whitespace and
// separators (\p{Z}), control/format/zero-width chars (\p{C}), lone combining marks (\p{M})
// and blank-looking letters (Hangul fillers, halfwidth filler, braille blank)
function hasVisibleText($text){
    return preg_match('/[^\s\p{Z}\p{C}\p{M}\x{115F}\x{1160}\x{3164}\x{FFA0}\x{2800}]/u', $text) === 1;
}

// Count UTF-8 characters without needing mbstring; invalid UTF-8 counts as too long
function charLength($text){
    $count = preg_match_all('/./us', $text);
    return $count === false ? PHP_INT_MAX : $count;
}

// Print a stored value safely. Rows are stored HTML-encoded, so decode then re-encode:
// form-posted rows render exactly as before, and raw HTML inserted any other way can't run.
function showStored($text){
    return htmlspecialchars(htmlspecialchars_decode((string)$text, ENT_QUOTES), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect($url){
    header('location:'.$url);
    exit;
}

function randomNumber() {
    return rand(1, 100);
}

// Generate two random numbers for captcha-like verification
$randNum1 = randomNumber();
$randNum2 = randomNumber();

$sumRandNum = $randNum1 + $randNum2;

// Check if the session variable "sumRandNum" exists
if (isset($_SESSION['sumRandNum'])) {
    // If it does, assign the previous value of sumRandNum to a new variable $previousSumRandNum
    $previousSumRandNum = $_SESSION['sumRandNum'];
} else {
    // If it doesn't exist, set $previousSumRandNum to null
    $previousSumRandNum = null;
}

// Update the value of sumRandNum in the session variable
$_SESSION['sumRandNum'] = $sumRandNum;
