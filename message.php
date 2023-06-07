<?php
if(!isset($_SESSION)){
    session_start();
}
include('config.php');
include('variableAndFunctions.php');

if (isset($_POST['action']) and $_POST['action'] == 'sendmessage') {
    $name = htmlspecialchars(escape($_POST['name']));
    $message = htmlspecialchars(escape($_POST['message']));
    $verification = ($_POST['verification']);
    $timedate = escape(date("Y-m-d H:i:s"));

    if (empty($name)) {
        $_SESSION['msg'] = "Input fields cannot be empty!";
        redirect('/index.php');
    } elseif (empty($message)) {
        $_SESSION['msg'] = "Input fields cannot be empty!";
        redirect('/index.php');
    } elseif (empty($verification)) {
        $_SESSION['msg'] = "Input fields cannot be empty!";
        redirect('/index.php');
    } else {
        if ($previousSumRandNum == $verification) {
            $name=mysqli_real_escape_string($dbconnect, htmlspecialchars($name));
            $message=mysqli_real_escape_string($dbconnect, htmlspecialchars($message));
            $messageQuery = "INSERT INTO `$dbname`.`$tablename` (`id`, `name`, `message`, `date`) VALUES (NULL, '$name', '$message', '$timedate');";
            $sendMessage = mysqli_query($dbconnect, $messageQuery);
            if ($sendMessage) {
                $_SESSION['msg'] = "Message Added!";
                unset($_SESSION['sumRandNum']);
                redirect('/index.php');
            } else {
                $_SESSION['msg'] = "Failed adding your message!";
                unset($_SESSION['sumRandNum']);
                redirect('/index.php');
                exit;
            }
        } else {
            $_SESSION['msg'] = "Verification failed!";
            unset($_SESSION['sumRandNum']);
            redirect('/index.php');
            exit;
        }
    }
} else {
    unset($_SESSION['sumRandNum']);
    redirect('/index.php');
}