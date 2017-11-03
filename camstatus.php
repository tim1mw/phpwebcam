<?php

require_once('lib.php');

$r=new stdclass();

try {
    $camkey = $_GET['camkey'];
    $camera = new WebCamHandler($camkey);
    $r->cameraOn = $camera->cameraOn();
    // If this is an admin, override maintenance mode
    if (isAdmin()) {
        $r->maintenanceMode = false;
    } else {
        $r->maintenanceMode = $camera->maintenanceMode();
    }

    $r->cameraOnline = $camera->cameraOnline();
    // If the camera has gone offline during operating hours, override the cameraOn value.
    if (!$r->cameraOnline && $r->cameraOn) {
        $r->cameraOn = false;
    }
} catch (CameraNotFoundException $e) {
    $r->cameraOn = false;
}

header('Content-type: application/json');
echo json_encode($r);