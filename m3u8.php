<?php

require_once("lib.php");

try {
    $camkey = $_GET['camkey'];
    $camera = new WebCamHandler($camkey);

    // Refuse to serve the file if we're in mainteance mode and this isn't an admin
    if ($camera->maintenanceMode() && !isAdmin()) {
        exit();
    }

    $str = "#EXTM3U\n";
    $streams = $camera->getStreams();
    foreach ($streams as $stream) {
        $file = "segments/".$camkey."-".$stream['bitrate_kbps']."/streaming.m3u8";
        // Check the file exists, if not wait fir the length of a segment before trying a second time and then skip it if it's still missing
        if (file_exists($file)) {
            $str.= "#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=".($stream['bitrate_kbps']*1000)."\n";
            $str.= $file."\n";
        } else {
            sleep($CONFIG['segment_time']);
            if (file_exists($file)) {
                $str.= "#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=".($stream['bitrate_kbps']*1000)."\n";
                $str.= $file."\n";
            }
        }
    }

    //header("Expires: "+gmdate('D, d M Y H:i:s T');
    header('Content-type: application/x-mpegURL');
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    echo $str;
} catch (CameraNotFoundException $e) {
    echo $e->getMessage();
}