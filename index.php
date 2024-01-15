<?php
if(!isset($_SESSION)){
    session_start();
}
include('config.php');
include('variableAndFunctions.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="https://castorisdead.xyz/assets/images/avatar/avatar.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="https://castorisdead.xyz/assets/images/avatar/avatar.png">
    <link rel="stylesheet" href="/assets/css/style.css">
    <title><?= $title ?></title>
</head>

<body>
    <div class="guestbookarea">
        <h1 id="welcome">Welcome to <?= $title ?>!</h1>
        <p id="madeby">Powered by <a href="https://github.com/whoisyoges/simple-guestbook" target="_blank">simple guestbook</a>.</p>
        
        <form action="message.php" method="post">
            <p class="inputid">Name:</p>
            <p><input type="text" name="name" class="inputfields" maxlength="60" placeholder="Your Name"></p>

            <p class="inputid">Message:</p>
            <p><textarea name="message" rows="3" maxlength="600" class="inputfields" placeholder="Your Message"></textarea></p>

            <p class="inputid">Verification</p>
            <p><?= $randNum1 ?> + <?= $randNum2 ?> is equal to: <input type="text" name="verification" id="verification" maxlength="3" placeholder="Your Answer"></p>

            <input type="submit" value="send" title="Send your message!">
            <input type="hidden" name="action" value="sendmessage">
        </form>
        <p id="sessionmsg">
            <?php
            if(isset($_SESSION['msg'])) {
                echo $_SESSION['msg'];
                unset ($_SESSION['msg']);
            }
            ?>
        </p>
        <?php
        // Get the current page number
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

        // Calculate the start point of the record set for the current page
        $start = ($page - 1) * $per_page;

        // Get the total number of messages
        $showMessage = "SELECT * FROM `$dbname`.`$tablename` order by id desc";
        $execMessage = mysqli_query($dbconnect,$showMessage);
        $total = mysqli_num_rows($execMessage);
    
        // Calculate the total number of pages
        $num_pages = ceil($total / $per_page);

        // Select messages for current page
        $showPageMessage = "SELECT * FROM `$dbname`.`$tablename` ORDER BY id DESC LIMIT $start, $per_page";
        $execPage = mysqli_query($dbconnect,$showPageMessage);

        while ($result = mysqli_fetch_assoc($execPage)):
        ?>

        <div class="outputarea">
            <div class="user">
                <p><?= ($result['name']) ?><span><?= ($result['date']) ?> <?=$timezone?></span></p>
            </div>
            <p><?= ($result['message']) ?></p>
        </div>
        <?php
            endwhile;                
        ?>

        <div class="pagination">
            <?php
            // Generate pagination link
            for ($i = 1; $i <= $num_pages; $i++) {
                if ($i == $page) {
                    echo '<a id="currentpage" href="?page=' . $i . '">' . $i . '</a>';
                } else {
                    echo '<a href="?page=' . $i . '">' . $i . '</a>';
                }
            }
            ?>

        </div>
    </div>
</body>

</html>
<?php
// Close the connection
mysqli_close($dbconnect); 
?>
