<?php
session_start();

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
    // If it does, assign the previous value of a to a new variable $previousA
    $previousSumRandNum = $_SESSION['sumRandNum'];
} else {
    // If it doesn't exist, set $previousA to null
    $previousSumRandNum = null;
}

// Update the value of a in the session variable
$_SESSION['sumRandNum'] = $sumRandNum;
