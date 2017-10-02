<?php

require_once('lib.php');
?>
<html>
    <head>
        <title>Webcam Admin login</title>
    </head>
    <body>
    <h1>Webcam Admin</h1>

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
        adminOptions();
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

<?php
function adminOptions() {
    ?>
    <p>Logged in as admin</p><p><a href='admin.php?logout'>Logout</a></p>
    <h4>Available cameras</h4>
    <table style="border:1px solid black";>
        <tr>
            <th style="border:1px solid black;">Camera name</td>
            <th style="border:1px solid black;">Hours</td>
            <th style="border:1px solid black;">Stream PID's</td>
            <th style="border:1px solid black;">Maintenance</td>
            <th style="border:1px solid black;">Actions</td>
        </tr>

        <?php
        $cams = WebCamHandler::getAllCamKeys();
        foreach ($cams as $camkey) {
            $camera = new WebCamHandler($camkey);
            echo "<tr>\n".
                "    <td style='border:1px solid black;'><a href='/camview.php?camkey=".$camkey."'>".$camera->getName()."</a></td>\n".
                "    <td style='border:1px solid black;'>".$camera->startTime()." - ".$camera->finishTime()."</td>".
                "    <td style='border:1px solid black;text-align:center;'>".$camera->streamStatus()."</td>".
                "    <td style='border:1px solid black;text-align:center;'>".$camera->maintenanceMode()."</td>".
                "    <td style='border:1px solid black;'><a href='/camrestart.php?camkey=".$camkey."'>Restart feed</td>\n".
                "</tr>";
        }
        ?>
    </table>
<?php
}