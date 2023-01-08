<?php

    // website name/title
    $title = "Castor's Guestbook";

    // messages to display per page
    $per_page = 10;
    
    // database server credientials
    $hostname = 'localhost';
    $hostuser = 'root';
    $hostpass = '';
    $dbname = 'castor_guestbook'; //database name
    $tablename = 'guestbook'; //table name in the database

    //connection between php and mysql (Do not change this!!!)
    $dbconnect = mysqli_connect($hostname,$hostuser,$hostpass,$dbname);

    // Check connection
    if(!$dbconnect) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Create the required table if not found in the database
    // CREATE TABLE `$dbname`.`$tablename` (`id` INT AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(60) NOT NULL, `message` TEXT(600) NOT NULL, `date` DATETIME DEFAULT NOW() NOT NULL) AUTO_INCREMENT=1;