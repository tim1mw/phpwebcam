<?php

require_once('lib.php');
?>
<html>
    <head>
        <title>Webcam Admin login</title>
        <link href="video-js.min.css" rel="stylesheet" />
        <link href="style.css" rel="stylesheet" />
        <script type='text/javascript' src="video.min.js"></script>
        <script type='text/javascript' src="videojs-contrib-hls.min.js"></script>
    </head>
    <body>
    <h1>Webcam Admin login</h1>

    <?php
    session_start();

    if (array_key_exists('logout', $_GET)) {
        $_SESSION['admin'] = false;
        session_destroy();
        ?><p>Logged out</p><?php
    }

    if (array_key_exists('password', $_POST)) {
         if ($_POST['password'] == $CONFIG['adminpass']) {
             $_SESSION['admin'] = true;
         } else {
             ?><p>Incorrect password.</p><?php
         }
    }

    if (array_key_exists('admin', $_SESSION) && $_SESSION['admin']===true) {
        ?><p>Logged in.</p><p><a href='admin.php?logout'>Logout</a></p><?php
    } else {
    ?>
        <form action="admin.php" method='post'>
        <p>Admin password: <input type='password' name='password' /></p>
        </form>
        </body>
    <?php
    }


    ?>
    </body>
</html>
