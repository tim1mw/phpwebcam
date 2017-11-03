<?php include('setup.php'); ?>
<!doctype html>
<html>
    <head>
        <title><?php echo $camera->getName(); ?></title>
        <link href="video-js.min.css" rel="stylesheet" />
        <link href="style-cam.css" rel="stylesheet" />
        <script type='text/javascript' src="video.min.js"></script>
        <script type='text/javascript' src="videojs-contrib-hls.min.js"></script>
        <script type='text/javascript'>
            var camKey="<?php echo $camkey ?>";
            var camOn=<?php if ($camera->cameraOn() && $camera->cameraOnline()) {echo "true";} else {echo "false";} ?>;
            var maintenanceMode=<?php 
                if (isAdmin()) {
                    echo "false";
                } else {
                    if ($camera->maintenanceMode()) {
                        echo "true";
                    } else {
                        echo "false";
                    }
                }
            ?>;
            var cameraOnline=<?php
                if ($camera->cameraOnline()) {
                    echo "true";
                } else {
                    echo "false";
                } ?>;
            var startTime="<?php echo $camera->startTime(); ?>";
            var endTime="<?php echo $camera->finishTime()." ".date('T'); ?>";
        </script>
        <script type='text/javascript' src="camera.js"></script>
    </head>
    <body>
        <div class="outer-container">
            <div class="controls"><table>
                 <tr>
                     <td class='link' id='link-live'>
                         <a href="javascript:setView('live');" title="Watch the live video feed">Live Video</a>
                     </td>
                     <td class='link' id='link-clip'>
                         <a href="javascript:setView('clip');" title="Watch pre-recorded clips from the past few days">Video Clips</a>
                     </td>
                     <td class='link' id='link-image'>
                         <a href="javascript:setView('image');" title="Look at selected images from the past few days">Still Images</a>
                     </td>
                </tr>
            </table></div>
            <div class="inner-container">
                <div id='live'>
                <div class="video-overlay">
                    <img class='channel-mask' src='<?php echo $camera->getChannelMask(); ?>' />
                    <div class='warn' id='iewarn'></div>
                </div>

                <div id='online'>
                    <video id="videojs-player" class="video-js vjs-default-skin vjs-big-play-centered" width="790" height="444"
                       data-setup='{
                            "controls": true,
                            "autoplay": false,
                            "poster": "<?php echo $camera->getPosterImage(); ?>",
                            "controlBar": { "muteToggle": false, "volume": false }}'>
                        <source src="m3u8.php?camkey=<?php echo $camkey?>" type="application/x-mpegURL">
                    </video>
                </div>

                <div id='offline' style='background-image:url(<?php echo $camera->getPosterImage(); ?>)'>
                    <div class='warn' id='offlinewarn'></div>
                </div>
                </div>
                <div id='other'></div>
            </div>
        </div>
        <noscript><p style="text-align:center">This webcam requires an HTML 5 compatible broswer and Javascript</p></noscript>
    </body>
</html>