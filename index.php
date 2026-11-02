<?php
include('variableAndFunctions.php');
include('config.php');
include('setup.php');

// Remember when the form was shown, so instant (scripted) submits can be rejected
$_SESSION['formShownAt'] = time();
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
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
</head>

<body>
    <div class="guestbookarea">
        <h1 id="welcome">Welcome to <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>!</h1>
        <p id="madeby">Powered by <a href="https://github.com/whoisyoges/simple-guestbook" target="_blank">simple guestbook</a>.</p>

        <form action="message.php" method="post">
            <p class="inputid">Name:</p>
            <p><input type="text" name="name" class="inputfields" maxlength="<?= $maxNameLength ?>" placeholder="Your Name"></p>

            <p class="inputid">Message:</p>
            <p><textarea name="message" rows="3" maxlength="<?= $maxMessageLength ?>" class="inputfields" placeholder="Your Message"></textarea></p>

            <!-- Honeypot: hidden from people, bots tend to fill it in -->
            <p class="hp" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></p>

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
        $page = isset($_GET['page']) && is_string($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

        // Calculate the start point of the record set for the current page
        $start = ($page - 1) * $per_page;

        // Get the total number of messages
        $showMessage = "SELECT COUNT(*) FROM `$dbname`.`$tablename`";
        $execMessage = mysqli_query($dbconnect,$showMessage);
        $total = (int)mysqli_fetch_row($execMessage)[0];

        // Calculate the total number of pages
        $num_pages = (int)ceil($total / $per_page);

        // Select messages for current page
        $showPageMessage = "SELECT * FROM `$dbname`.`$tablename` ORDER BY id DESC LIMIT $start, $per_page";
        $execPage = mysqli_query($dbconnect,$showPageMessage);

        while ($result = mysqli_fetch_assoc($execPage)):
        ?>

        <div class="outputarea">
            <div class="user">
                <p><bdi><?= showStored($result['name']) ?></bdi><span><?= showStored($result['date']) ?> <?= showStored($timezone) ?></span></p>
            </div>
            <p class="message"><?= showStored($result['message']) ?></p>
        </div>
        <?php
            endwhile;
        ?>

        <div class="pagination">
            <?php
            // Generate pagination links: first, last, and two pages either side of the current one
            $lastShown = 0;
            for ($i = 1; $i <= $num_pages; $i++) {
                if ($i != 1 && $i != $num_pages && abs($i - $page) > 2) {
                    continue;
                }
                if ($i - $lastShown > 1) {
                    echo '<span>…</span>';
                }
                if ($i == $page) {
                    echo '<a id="currentpage" href="?page=' . $i . '">' . $i . '</a>';
                } else {
                    echo '<a href="?page=' . $i . '">' . $i . '</a>';
                }
                $lastShown = $i;
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
