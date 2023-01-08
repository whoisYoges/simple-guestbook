<?php
    include('config.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="https://castorisdead.xyz/assets/images/avatar/avatar.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="https://castorisdead.xyz/assets/images/avatar/avatar.png">
    <link rel="stylesheet" href="assets/css/style.css">
    <title><?= $title ?></title>
</head>

<body>
    <div class="guestbookarea">
        <h1 id="welcome">Welcome to Castor's guestbook!</h1>
        <p id="madeby">Powered by <a href="https://github.com/whoisyoges/simple-guestbook" target="_blank">simple guestbook</a>.</p>
        
        <form action="message.php" method="post" enctype="multipart/form-data">
            <p class="inputid">Name:</p>
            <p><input type="text" name="name" class="inputfields" maxlength="60" placeholder="Your Name"></p>

            <p class="inputid">Message:</p>
            <p><textarea name="message" rows="3" maxlength="600" class="inputfields" placeholder="Your Message"></textarea></p>

            <input type="hidden" name="action" value="message">
            <input type="submit" value="send" title="Send your message!">
        </form>
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
                <p><?= $result['name']?><span><?= $result['date']?> UTC</span></p>
            </div>
            <p><?= $result['message']?></p>
        </div>
        <?php
            endwhile;                
        ?>

        <!-- Generate the pagination links -->
        <div class="pagination">
            <?php
            for ($i = 1; $i <= $num_pages; $i++) {
                if ($i == $page) {
                    echo '<span class="current">' . $i . '</span>';
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
mysqli_close($conn);
?>