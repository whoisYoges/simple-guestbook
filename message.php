<?php
    include('config.php');
    if (isset($_POST['action']) && $_POST['action'] == 'message') {
        $name = htmlspecialchars($_POST['name']);
        $message = htmlspecialchars($_POST['message']);
        $timedate = gmdate("Y-m-d H:i:s");

        $sendMessage = "INSERT INTO `$dbname`.`$tablename` (`id`, `name`, `message`, `date`) VALUES (NULL, '$name', '$message.', current_timestamp());";
        
        if(mysqli_query($dbconnect,$sendMessage)){
            echo "Message added.";
        }
        else {
            echo "Couldn't add your message.";
        }
    }
    header('Location: index.php');
    exit;