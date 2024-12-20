<?php

require_once("config.php");

class WebCamHandler {

    private $camdata;
    private $camkey;
    private $dir;

    function __construct($camera) {
        global $CAMERAS;

        if (!array_key_exists($camera, $CAMERAS)) {
            throw new CameraNotFoundException($camera);
        }

        $this->camkey = $camera;
        $this->camdata = $CAMERAS[$camera];
        $this->dir = dirname(__FILE__);
    }

    function getCamKey() {
        return $this->camkey;
    }

    function getName() {
        return $this->camdata['name'];
    }

    function getChannelMask() {
        return "images/".$this->camdata['channel_mask'];
    }

    function getPosterImage() {
        // If there is no poster, use the most recent still
        if ($this->camdata['poster_image'] === false) {
            $stream = $this->firstStream();
            $images = $this->getFiles($this->getStoreDir($stream, 'stills', false)."/*.jpg");
            foreach ($images as $image) {
                if (file_exists($image) && strpos($image, "thumb") === false) {
                    return 'stills/'.$this->camkey.'/'.basename($image);
                }
            }
            return '';
        }

        return "images/".$this->camdata['poster_image'];
    }

    function getStreams() {
        return $this->camdata['camera_streams'];
    }

    static function getAllCamKeys() {
        global $CAMERAS;
        $cams = array();
        foreach ($CAMERAS as $key => $value) {
            $cams[] = $key;
        }

        return $cams;
    }

    function startTime() {
        return $this->convertDate($this->camdata['start']);
    }

    function finishTime() {
        return $this->convertDate($this->camdata['finish']);
    }

    function finishTimeStamp() {
        $date=date("Y-m-d ").$this->starttime();
        return time("Y-m-d H:i", $date);
    }

    function getMaintenanceMessage() {
        global $CONFIG;
        $mfile = $CONFIG['tmp_dir'].'/'.$this->camkey.'.message';
        if (file_exists($mfile)) {
            echo file_get_contents($mfile);
        } else {
            return "This webcam is offline for maintenance, sorry for the inconvenience.";
        }
    }

    private function convertDate($date) {
        return substr($date, 0, 2).":".substr($date, 2);
    }

    function cameraOn($offset = false) {

        if (isAdmin() && $this->maintenanceMode()) {
            return true;
        }

        $start = $this->camdata['start'];
        $finish = $this->camdata['finish'];

        if ($offset) {
            $start--;
            $finish++;
        }

        $now = date("Hi");
        if ($now > $this->camdata['start'] && $now < $this->camdata['finish'] && !$this->maintenanceMode()) {
            return true;
        } else {
            return false;
        }
    }

    function cameraOnline() {
        global $CONFIG;
        $countfile = $CONFIG['tmp_dir'].'/'.$this->camkey.'.nettest';
        if (!file_exists($countfile)) {
            $this->cameraOnlineTest();
        }
        $fail_count = intval(file_get_contents($countfile));

        if ($fail_count >= $CONFIG['max_fail_count']) {
            return false;
        }
        return true;
    }

    function cameraOnlineTest() {
        global $CONFIG;

        if ($this->camdata['ping_test']) {
            $str = exec("ping -c 1 ".$this->camdata['ping_test']);
            $countfile = $CONFIG['tmp_dir'].'/'.$this->camkey.'.nettest';
            if (!$str){
                if (!file_exists($countfile)) {
                    $fail_count = 0;
                } else {
                    $fail_count = intval(file_get_contents($countfile));
                }
                $fail_count++;
                file_put_contents($countfile, $fail_count);
                if ($fail_count >= $CONFIG['max_fail_count']) {
                    return false;
                }
            } else {
                file_put_contents($countfile, '0');
            }
        }

        return true;
    }

    function maintenanceMode() {
        //return $this->camdata['maintenance'];
        global $CONFIG;
        $mfile = $CONFIG['tmp_dir'].'/'.$this->camkey.'.maintenance';
        if (file_exists($mfile)) {
            return true;
        } else {
            return false;
        }
    }

    function setMaintenanceMode($mode) {
        global $CONFIG;
        $mfile = $CONFIG['tmp_dir'].'/'.$this->camkey.'.maintenance';
        if ($mode) {
            file_put_contents($mfile, 'on');
        } else {
            unlink($mfile);
        }
    }

    function checkStreams() {
        // When we are in maintenance mode, start the camera, otherwise obey cameraOn.
        if ($this->maintenanceMode()) {
            $runcam = true;
        } else {
            $runcam = $this->cameraOn(true);
        }

        if (!$this->cameraOnlineTest()) {
            return;
        }

        if ($runcam) {
            $this->startStreams();
        } else {
            $this->stopStreams();
        }
    }

    function startStreams() {
        foreach ($this->camdata['camera_streams'] as $stream) {
            if (!$this->streamRunning($stream)) {
                $this->startStream($stream);
            }
        }
    }

    function stopStreams() {
        foreach ($this->camdata['camera_streams'] as $stream) {
            if ($this->streamRunning($stream)) {
                $this->stopStream($stream);
            }
        }
    }

    function streamStatus() {
        $status = "";
        foreach ($this->camdata['camera_streams'] as $stream) {
            $status .= $stream['bitrate_kbps']."kbps: ".$this->streamRunning($stream)."<br />";
        }
        return $status;
    }

    function streamRunning($stream) {
        $url = $this->camdata['camera_base_url'].$stream['url_part'];

        switch ($this->camdata['stream_type']) {
            case 'rtsp':
               $out = shell_exec("ps -f -C openRTSP | grep ".$url." | awk '{print $2}'");
               break;
            case 'rtmp':
            case 'rtsplisten':
               $out = shell_exec("ps -f -C ffmpeg | grep ".$url." | awk '{print $2}'");
        }
        
        if (strlen($out) > 0) {
            return intval($out);
        }

        return false;
    }

    function startStream($stream) {
        global $CONFIG;
        $storedir = $this->getStoreDir($stream, 'segments');
        if (!file_exists($storedir)) {
            mkdir($storedir);
        }

        $m3u8_file = $storedir."/streaming.m3u8";
        $ts_file = $storedir."/streaming%d.ts";
        $start_number = 0;
        // If the m3u8 file exists and is less than 3 mins old, try setting the media sequence to persuade clients to continue playing.
        if (file_exists($m3u8_file) && time()-filemtime($m3u8_file) < 180) {
            $m3u8 = trim(file_get_contents($m3u8_file));
            $m3u8_parts = explode("\n", $m3u8);
            // The last line should be the most recent segment written, get it's number and add 1
            $last = end($m3u8_parts);
            $last = substr($last, 9);
            $index = strpos($last, '.');
            $last = substr($last, 0, $index);
            $start_number = intval($last)+1;
        }
        else {
            shell_exec("rm -f ".$storedir."/*");
        }

        $command = $this->getbaseCommand($stream, $m3u8_file, $start_number).
            " ".$CONFIG['ffmpeg_encode']." ".$ts_file;

        $command .= " > ".$CONFIG['log_dir']."/".$this->camkey."_".$stream['url_part']."_".date('Y-m-d_H:i:s').".log 2>&1";

        echo $command."\n";
        if (php_sapi_name() != "cli") {
            ob_flush();
        }
        flush();

        $script_file = $this->getScriptFile($stream);
        if (file_exists($script_file)) {
            unlink($script_file);
        }
        file_put_contents($script_file, "#!/bin/bash\n".$command."\n");
        shell_exec("chmod a+x ".$script_file);
        shell_exec("nohup ".$script_file." > /dev/null & echo $!");
        // FFMPEG dies if we start too many instances too close together talking to the same camera, so pause for a moment.
        sleep(1);
    }

    function stopStream($stream) {
        global $CONFIG;
        $pid = $this->streamRunning($stream);
        if ($pid) {
            shell_exec("kill ".$pid);
        }

        $storedir = $this->getStoreDir($stream, 'segments');
        $m3u8_file = $storedir."/streaming.m3u8";
        file_put_contents($m3u8_file, "#EXTM3U\n");
        unlink($this->getScriptFile($stream));
    }

    function getStoreDir($stream, $type, $bps=true) {
        global $CONFIG;
        $sdir = $this->dir."/".$type."/".$this->camkey;
        if ($bps) {
            $sdir.="-".$stream['bitrate_kbps'];
        }
        return $sdir;
    }

    function getScriptFile($stream) {
        global $CONFIG;
        return $CONFIG['tmp_dir']."/".$this->camkey."-".$stream['bitrate_kbps'].".sh";
    }

    function getBaseCommand($stream, $m3u8_file, $start_number) {
        global $CONFIG;

        $command = "";

        switch ($this->camdata['stream_type']) {
            case 'rtsp':
                $command .= $CONFIG['openrtsp']." -D 30 -v ".$stream['rtsp_params']." -c -b ".($stream['bitrate_kbps']*20000).
                    " -B ".($stream['bitrate_kbps']*20000)." ".$this->camdata['camera_base_url'].$stream['url_part']." | ";
                $command .= $CONFIG['ffmpeg']." -r ".$stream['frame_rate']." -i -";
                break;
            case 'rtmp':
                $command .= $CONFIG['ffmpeg']." -r ".$stream['frame_rate']." -i ".$this->camdata['camera_base_url'].$stream['url_part'];
                if (array_key_exists('username', $this->camdata)) {
                    $command .= ":".$this->camdata['username'].":".$this->camdata['password'];
                }
                break;
            case 'rtsplisten':
                $command .= $CONFIG['ffmpeg']." -analyzeduration 2147483647 -probesize 2147483647 -rtsp_flags listen -timeout -1 -i ".$this->camdata['camera_base_url']."  -use_wallclock_as_timestamps 1";
                break;
        }

        if ($this->camdata['fix_stream']) {
            $command .= " ".$CONFIG['ffmpeg_fix'];
        }
/*
        $command .= " -bufsize ".($stream['bitrate_kbps']*2000)." -timeout 60".
            " -segment_list_flags +live -hls_allow_cache 0 ".
            " -hls_flags temp_file+omit_endlist -hls_time ".$CONFIG['segment_time'].
            " -segment_wrap ".$CONFIG['segment_wrap'].
            " -hls_list_size 15 ".
            " -muxpreload 15 -muxdelay 15";
            //" -hls_flags delete_segments -hls_list_size ".$CONFIG['segment_wrap'];
*/

        $command .= " -bufsize ".($stream['bitrate_kbps']*2000)." -timeout 60".
            " -muxpreload 15 -muxdelay 15".
            " -f segment -segment_list ".$m3u8_file.
            " -segment_list_flags +live -segment_wrap ".$CONFIG['segment_wrap'].
            " -hls_allow_cache 0 -hls_flags temp_file+omit_endlist ".
            " -segment_time ".$CONFIG['segment_time'].
            " -start_number ".$start_number;

        return $command;
    }

    function parseTimes($times) {
        $now = intval(date('Hi'));
        $time = -1;

        foreach ($times as $t) {
            if ($now >= $t && $now-$t < 5) {
                $time = $t;
                break;
            }
        }
        return $time;
    }

    function captureImage() {
        $times = $this->camdata['still_times'];
        $time = $this->parseTimes($times);
        if ($time == -1) {
            return;
        }

        $stream = $this->firstStream();
        $storedir = $this->getStoreDir($stream, 'stills', false);

        if (!file_exists($storedir)) {
            mkdir($storedir);
        }

        $saveto = $storedir."/".date('Ymd')."-".$time;

        if (file_exists($saveto.".jpg")) {
            return;
        }

        if ($this->cameraOn()) {
            $this->captureImageOn($stream, $saveto.".jpg");
        } else {
            $this->captureImageOff($stream, $saveto.".jpg");
        }

        $this->makeThumb($saveto.".jpg", $saveto."-thumb.jpg", 125);

        $images = $this->getFiles($storedir."/*jpg");
        $maximage = 70;
        if (count($images) > $maximage) {
            for ($loop=$maximage+1 ; $loop<count($images); $loop++) {
                unlink($images[$loop]);
            }
        }
    }

    function firstStream() {
        $strs = $this->camdata['camera_streams'];
        reset($strs);
        return current($strs);
    }

    function captureImageOn($stream, $saveto) {echo "on";
        global $CONFIG;
        $files = $this->getFiles($this->getStoreDir($stream, 'segments')."/*.ts");
        if (count($files) < 2) {
            $this->captureImageOff($stream);
            return;
        }

        shell_exec($CONFIG['ffmpeg']." -loglevel panic -i ".$files[1]." -vframes 1 ".$saveto);
    }

    function captureImageOff($stream, $saveto) {echo "off";
        global $CONFIG;
        shell_exec($this->getBaseCommand($stream)." -vframes 1 ".$saveto);
    }

    function makeThumb($src, $dest, $desired_width) {
        /* read the source image */
        $source_image = imagecreatefromjpeg($src);
        $width = imagesx($source_image);
        $height = imagesy($source_image);

        /* find the "desired height" of this thumbnail, relative to the desired width  */
        $desired_height = floor($height * ($desired_width / $width));

        /* create a new, "virtual" image */
        $virtual_image = imagecreatetruecolor($desired_width, $desired_height);

        /* copy source image at a resized size */
        imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height);

        /* create the physical thumbnail image to its destination */
        imagejpeg($virtual_image, $dest);
    }

    function getThumbnails() {
        $stream = $this->firstStream();
        $images = $this->getFiles($this->getStoreDir($stream, 'stills', false)."/*thumb.jpg");

        $paths=array();
        $count=0;
        foreach ($images as $image) {
            $path = new stdclass();
            $path->thumb = 'stills/'.$this->camkey.'/'.basename($image);
            $path->img = str_replace('-thumb.jpg', '.jpg', $path->thumb);
            // Check that the actual full size still exists, just in case it got deleted manually or the time stamp got changed causing early deletion
            if (!file_exists($path->img)) {
                continue;
            }
            $path->time = date ("dS F Y, H:i", filemtime($image));
            $paths[] = $path;
            $count++;
            if ($count>=30) break;
        }
        return $paths;
    }

    function getVideoClips() {
        $stream = $this->firstStream();
        $clips = $this->getFiles($this->getStoreDir($stream, 'vclips', false)."/*");

        $paths=array();
        $count=0;
        foreach ($clips as $clip) {
            $path = new stdclass();
            $b = basename($clip);
            $path->thumb = 'vclips/'.$this->camkey.'/'.$b.'/thumb.jpg';
            // If the thumbnail file is missing, something must have gone wrong capturing the clip, so skip it
            if (!file_exists($path->thumb)) {
                continue;
            }
            $path->link = 'clipshow.php?camkey='.$this->camkey."&amp;clip=".$b;
            $path->time = file_get_contents('vclips/'.$this->camkey.'/'.$b.'/date.txt');
            $paths[] = $path;
            $count++;
            if ($count>=30) break;
        }
        return $paths;
    }

    function getFiles($dir) {
        $files = glob($dir);
        usort($files, function($a, $b) {
            return filemtime($a) < filemtime($b);
        });
        return $files;
    }

    function captureVclip() {
        global $CONFIG;

        if (!$this->cameraOn()) {
            return;
        }

        $times = $this->camdata['vclip_times'];
        $time = $this->parseTimes($times);
        if ($time == -1) {
            return;
        }

        $stream = $this->firstStream();

        $storedir = $this->getStoreDir($stream, 'vclips', false);
        if (!file_exists($storedir)) {
            mkdir($storedir);
        }

        $saveto = $storedir."/".date('Ymd')."-".$time;

        if (file_exists($saveto)) {
            return;
        }

        mkdir($saveto, 0777, true);
        $segment_dir = $this->getStoreDir($stream, 'segments');
        $segments = $this->getFiles($segment_dir."/*.ts");

        if (count($segments) == 0) {
            return;
        }

        $command = "cat ";
        for ($loop = count($segments)-1; $loop>=0; $loop--) {
            $command .= $segments[$loop]." ";
        }
        $command .= " > ".$saveto."/combined.ts;";
        shell_exec($command);

        file_put_contents($saveto."/date.txt", date("dS F Y, H:i", filemtime(end($segments))));

        shell_exec($CONFIG['ffmpeg']." -loglevel panic -i ".$saveto."/combined.ts -vframes 1 ".$saveto.'/image.jpg');
        $this->makeThumb($saveto."/image.jpg", $saveto."/thumb.jpg", 125);

        shell_exec($CONFIG['ffmpeg']." -loglevel panic -i ".$saveto."/combined.ts -hls_playlist_type vod -acodec copy -vcodec copy -hls_list_size 0 -hls_time ".$CONFIG['segment_time']." ".$saveto."/clip.m3u8");
        unlink($saveto."/combined.ts");

        $vclips = $this->getFiles($storedir."/*");
        $maxvclip = 30;
        if (count($vclips) > $maxvclip) {
            for ($loop=$maxvclip+1; $loop<count($vclips); $loop++) {
                shell_exec("rm -rf ".$vclips[$loop]);
            }
        }

    }
}


function isAdmin() {
    if (php_sapi_name() == "cli") {
        return true;
    }

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (array_key_exists('admin', $_SESSION) && $_SESSION['admin']===true) {
        return true;
    } else {
        return false;
    } 
}

class CameraNotFoundException extends Exception {

    function __construct($camera) {
        $trace = $this->getTrace();
        $message = "The requested camera '".$camera."' cannot be found on this server.";
        parent::__construct($message);
    }

}
