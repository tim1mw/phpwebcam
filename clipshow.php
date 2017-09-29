<?php

include('setup.php');

$stream = $_GET['stream'];
$clip = $_GET['clip'];

?>
<!doctype html>
<html>
    <head>
        <title><?php echo $camera->getName(); ?></title>
        <link href="video-js.min.css" rel="stylesheet" />
        <link href="style-cam.css" rel="stylesheet" />
        <script type='text/javascript' src="video.min.js"></script>
        <script type='text/javascript' src="videojs-contrib-hls.min.js"></script>
    </head>
    <body>
        <div class="outer-container">
            <div class="inner-container">
                <div class="video-overlay">
                    <img class='channel-mask' src='<?php echo $camera->getChannelMask(); ?>' />
                </div>
                <video id="videojs-player" class="video-js vjs-default-skin vjs-big-play-centered" width="790" height="444"
                       data-setup='{
                            "controls": true,
                            "autoplay": false,
                            "poster": "vclips/<?php echo $camera->getCamKey().'-'.$stream.'/'.$clip.'/image.jpg' ?>",
                            "controlBar": { "muteToggle": false, "volume": false }}'>
                    <source src="vclips/<?php echo $camera->getCamKey().'-'.$stream.'/'.$clip.'/clip.m3u8'?>" type="application/x-mpegURL">
                </video>
            </div>
        </div>
        <script type="text/javascript">
            var player;
            window.addEventListener("load", function () {
                player = videojs('videojs-player');
            });
        </script>
    </body>
</html>