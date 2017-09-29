<?php
require_once('lib.php');

try {
    $camkey = $_GET['camkey'];
    $camera = new WebCamHandler($camkey);
} catch (CameraNotFoundException $e) {
?>
<!doctype html>
<html>
    <head>
       <title>Webcam error</title>
    </head>
    <body>
        <p><?php echo $e->getMessage()?></p>
    </body>
</html>
<?php
    exit;
}
?>