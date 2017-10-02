<html>
    <head>
        <title>Webcam Restart</title>
    </head>
    <body>
    <h1>Restart Camera</h1>
    <pre>
<?php
    ob_implicit_flush(true);
    require_once('lib.php');

    try {
        $camkey = $_GET['camkey'];
        $camera = new WebCamHandler($camkey);
        ob_end_flush();

        echo "Restarting ".$camera->getName()."\n";
        echo "Stopping....\n";
        $camera->stopStreams();

        sleep(7);
        echo "Starting....\n";
        $camera->startStreams();

        ob_start();
    } catch (CameraNotFoundException $e) {
        echo $e->getMessage();
    }
?>
Finished
    </pre>

    <p><a href="admin.php">Back to admin page</a></p>
    </body>
</html>