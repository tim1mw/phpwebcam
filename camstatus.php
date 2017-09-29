<?php

require_once('lib.php');

$r=new stdclass();

try {
    $camkey = $_GET['camkey'];
    $camera = new WebCamHandler($camkey);
    $r->cameraOn = $camera->cameraOn();
    $r->maintenanceMode = $camera->maintenanceMode();
} catch (CameraNotFoundException $e) {
    $r->cameraOn = false;
}

header('Content-type: application/json');
echo json_encode($r);