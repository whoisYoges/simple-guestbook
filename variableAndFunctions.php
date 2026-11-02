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

function escape ($field){
    global $dbconnect;
    return mysqli_escape_string($dbconnect,$field);
}

function redirect($url){
    header('location:'.$url);
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
    // If it does, assign the previous value of sumRandNum to a new variable $previousSunRandNum
    $previousSumRandNum = $_SESSION['sumRandNum'];
} else {
    // If it doesn't exist, set $previousSunRandNum to null
    $previousSumRandNum = null;
}

// Update the value of sunRandNum in the session variable
$_SESSION['sumRandNum'] = $sumRandNum;
