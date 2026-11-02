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

    // Anti-spam state for this visitor
    $formShownAt = $_SESSION['formShownAt'] ?? null;
    $lastPostAt = $_SESSION['lastPostAt'] ?? 0;
    unset($_SESSION['formShownAt']);

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
    } elseif (postField('website') !== '' || $formShownAt === null || time() - $formShownAt < $minFormSeconds) {
        // Honeypot field was filled in, or the form was submitted without loading it / too fast
        $_SESSION['msg'] = "Verification failed!";
        unset($_SESSION['sumRandNum']);
        redirect('/index.php');
    } elseif (time() - $lastPostAt < $sessionCooldown) {
        $_SESSION['msg'] = "Please wait a bit before posting again!";
        unset($_SESSION['sumRandNum']);
        redirect('/index.php');
    } else {
        if ($previousSumRandNum == $verification) {
            try {
                // Site-wide flood limit, using the existing date column (no schema change)
                $floodQuery = mysqli_prepare($dbconnect, "SELECT COUNT(*) FROM `$dbname`.`$tablename` WHERE `date` > ?");
                $floodSince = date("Y-m-d H:i:s", time() - $floodWindowMinutes * 60);
                mysqli_stmt_bind_param($floodQuery, 's', $floodSince);
                mysqli_stmt_execute($floodQuery);
                mysqli_stmt_bind_result($floodQuery, $recentMessages);
                mysqli_stmt_fetch($floodQuery);
                mysqli_stmt_close($floodQuery);

                if ($recentMessages >= $floodMaxMessages) {
                    $_SESSION['msg'] = "The guestbook is busy, please try again in a few minutes!";
                    unset($_SESSION['sumRandNum']);
                    redirect('/index.php');
                }

                $messageQuery = mysqli_prepare($dbconnect, "INSERT INTO `$dbname`.`$tablename` (`name`, `message`, `date`) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($messageQuery, 'sss', $name, $message, $timedate);
                $sendMessage = mysqli_stmt_execute($messageQuery);
            } catch (mysqli_sql_exception $e) {
                error_log((string)$e);
                $sendMessage = false;
            }
            unset($_SESSION['sumRandNum']);
            if ($sendMessage) {
                $_SESSION['lastPostAt'] = time();
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
