### Table of Contents
[What is simple-guestbook?](#simple-guestbook)  
[Features](#features)  
[Dependencies](#dependencies)  
[Configuration](#configuration)

# simple-guestbook

As it's name suggests, simple-guestbook is a super simple and lightweight guestbook made using php and mysql/MariaDB.  
Demo and preview at: <https://guestbook.yogeshlamichhane.com.np>.

![Simple Guestbook Preview](/preview_guestbook.jpg)

# Features
- Minimal
- Ad and javascript free
- Easy to setup and use
- No 3rd party libs are used
- No login required
- simple captcha like verification process for anti-spam

# Dependencies
- php
- mysql or mariadb (Or any other database; configure accordingly)

# Configuration
- All the configuration is done in [config.php](/config.php).
- A database and a table in that database should be created according to the settings you configured in [config.php](/config.php).

Practically,
1. Create a database (give any name) and then configure it accordingly in [config.php](/config.php).

```
CREATE DATABASE <dbname>
```

Replace `<dbname>` with your database name as configured in [config.php](/config.php).

```
CREATE TABLE <dbname>.<tablename>
    (`id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(60) NOT NULL,
    `message` TEXT(600) NOT NULL,
    `date` DATETIME DEFAULT NOW() NOT NULL) AUTO_INCREMENT=1;
```

Replace `<dbname>` with your database name and `<tablename>` with your tablename as configured in [config.php](/config.php)

The table should look like following:

![database preview](/preview_database.jpg)

**This repository is available in following platforms:**
<https://git.sr.ht/~whoisyoges/simple-guestbook>  
<https://codeberg.org/whoisyoges/simple-guestbook>  
<https://github.com/whoisyoges/simple-guestbook>
