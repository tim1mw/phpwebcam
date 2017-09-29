<?php

ini_set('register_argc_argv', 0);  

if (!isset($argc) || is_null($argc))
{ 
    echo 'Not CLI mode';
    exit;
}

require_once('lib.php');

$cams = WebCamHandler::getAllCamKeys();
foreach ($cams as $camkey) {
    $camera = new WebCamHandler($camkey);
    $camera->checkStreams();
    $camera->captureImage();
    $camera->captureVclip();
}