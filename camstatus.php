<?php

require_once('lib.php');

$r=new stdclass();

try {
    $camkey = $_GET['camkey'];
    $camera = new WebCamHandler($camkey);
    $r->cameraOn = $camera->cameraOn();
    // If this is an admin, override maintenance mode
    if (isAdmin()) {
        $r->maintenanceMode = $camera->maintenanceMode();
    } else {
        $r->maintenanceMode = false;
    }
} catch (CameraNotFoundException $e) {
    $r->cameraOn = false;
}

header('Content-type: application/json');
echo json_encode($r);