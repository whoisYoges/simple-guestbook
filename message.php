<?php
include('variableAndFunctions.php');
include('config.php');
include('setup.php');

if (isset($_POST['action']) and $_POST['action'] == 'sendmessage') {
    // Stored HTML-encoded (encoded once) to match the format of existing rows
    $name = htmlspecialchars(postField('name'), ENT_QUOTES, 'UTF-8');
    // Browsers count a newline as one character for maxlength but submit \r\n
    $rawMessage = str_replace("\r\n", "\n", postField('message'));
    $message = htmlspecialchars($rawMessage, ENT_QUOTES, 'UTF-8');
    $verification = postField('verification');
    $timedate = date("Y-m-d H:i:s");

    if ($name === '' || $message === '' || $verification === '') {
        $_SESSION['msg'] = "Input fields cannot be empty!";
        redirect('/index.php');
    } elseif (!hasVisibleText($name) || !hasVisibleText($rawMessage)) {
        $_SESSION['msg'] = "Name and message must contain visible text!";
        redirect('/index.php');
    } elseif (charLength($name) > $maxNameLength || charLength($rawMessage) > $maxMessageLength) {
        // Name is checked after encoding because that is what has to fit in the column
        $_SESSION['msg'] = "Name or message is too long!";
        redirect('/index.php');
    } else {
        if ($previousSumRandNum == $verification) {
            try {
                $messageQuery = mysqli_prepare($dbconnect, "INSERT INTO `$dbname`.`$tablename` (`name`, `message`, `date`) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($messageQuery, 'sss', $name, $message, $timedate);
                $sendMessage = mysqli_stmt_execute($messageQuery);
            } catch (mysqli_sql_exception $e) {
                error_log((string)$e);
                $sendMessage = false;
            }
            unset($_SESSION['sumRandNum']);
            if ($sendMessage) {
                $_SESSION['msg'] = "Message Added!";
            } else {
                $_SESSION['msg'] = "Failed adding your message!";
            }
            redirect('/index.php');
        } else {
            $_SESSION['msg'] = "Verification failed!";
            unset($_SESSION['sumRandNum']);
            redirect('/index.php');
        }
    }
} else {
    unset($_SESSION['sumRandNum']);
    redirect('/index.php');
}
