<?php

require_once('lib.php');

$r=new stdclass();

try {
    $camkey = $_GET['camkey'];
    $camera = new WebCamHandler($camkey);

    echo $camera->getMaintenanceMessage();

} catch (CameraNotFoundException $e) {
    echo "Invalid camkey";
}

