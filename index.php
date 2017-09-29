<?php
require_once('lib.php');
?>
<!doctype html>
<html>
    <head>
        <title>Webcam List</title>
        <link href="video-js.min.css" rel="stylesheet" />
        <link href="style.css" rel="stylesheet" />
        <script type='text/javascript' src="video.min.js"></script>
        <script type='text/javascript' src="videojs-contrib-hls.min.js"></script>
    </head>
    <body>
    <h1>Webcam List</h1>

    <ul>
        <?php
        $cams = WebCamHandler::getAllCamKeys();
        foreach ($cams as $camkey) {
            $camera = new WebCamHandler($camkey);
            echo "<li><a href='/camview.php?camkey=".$camkey."'>".$camera->getName()."</a></li>";
        }
        ?>
    </ul>
    </body>
</html>