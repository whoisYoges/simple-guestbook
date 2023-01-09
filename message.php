<?php
include('config.php');

function escape ($field){
    global $dbconnect;
    return mysqli_escape_string($dbconnect,$field);
}
function redirect($url){
    header('location:'.$url);
}

if (isset($_POST['action']) and $_POST['action'] == 'sendmessage') {
    $name = htmlspecialchars(escape($_POST['name']));
    $message = htmlspecialchars(escape($_POST['message']));
    $timedate = escape(date("Y-m-d H:i:s"));

    $messageQuery = "INSERT INTO `$dbname`.`$tablename` (`id`, `name`, `message`, `date`) VALUES (NULL, '$name', '$message', '$timedate');";

    $sendMessage = mysqli_query($dbconnect, $messageQuery);
    if ($sendMessage) {
        redirect('index.php');
    }  
}
else {
    redirect('index.php');
}