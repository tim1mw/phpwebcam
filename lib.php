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

    private function convertDate($date) {
        return substr($date, 0, 2).":".substr($date, 2);
    }

    function cameraOn($offset = false) {
        $start = $this->camdata['start'];
        $finish = $this->camdata['finish'];

        if ($offset) {
            $start--;
            $finish++;
        }

        $now = date("Hi");
        if ($now > $this->camdata['start'] && $now < $this->camdata['finish'] && !$this->camdata['maintenance']) {
            return true;
        } else {
            return false;
        }
    }

    function maintenanceMode() {
        return $this->camdata['maintenance'];
    }

    function checkStreams() {
        $runcam = $this->cameraOn(true);
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

    function streamRunning($stream) {
        $url = $this->camdata['camera_base_url'].$stream['url_part'];

        $out = shell_exec("ps -f -C ffmpeg | grep ".$url." | awk '{print $2}'");
        if (strlen($out) > 0) {
            return intval($out);
        }

        return false;
    }

    function startStream($stream) {
        global $CONFIG;

        shell_exec("rm -f ".$this->getStoreDir($stream, 'segments')."/*");

        $command = $this->getbaseCommand($stream).
            " ".$CONFIG['ffmpeg_encode']." ".$this->getStoreDir($stream, 'segments')."/streaming.m3u8";

        $command .= " > ".$CONFIG['log_dir']."/".$stream['url_part']."_".date('Y-m-d_H:i:s').".log 2>&1";

        echo $command."\n\n";

        shell_exec("nohup ".$command." > /dev/null & echo $!");
        // FFMPEG dies if we start too many instances too close together talking to the same camera, so pause for a moment.
        sleep(1);
    }

    function getStoreDir($stream, $type) {
        global $CONFIG;
        return $this->dir."/".$type."/".$this->camkey."-".$stream['bitrate_kbps'];
    }

    function getBaseCommand($stream) {
        global $CONFIG;

        $command = $CONFIG['ffmpeg']." -loglevel ".$CONFIG['log_level'];

        if ($this->camdata['fix_framerate']) {
            $command .= " -r ".$stream['frame_rate'];
        }
        if (array_key_exists('rtsp_transport', $this->camdata) && $this->camdata['rtsp_transport']) {
            $command .= " -rtsp_transport ".$this->camdata['rtsp_transport'];
        }
        $command .= " -i ".$this->camdata['camera_base_url'].$stream['url_part'];
        if ($this->camdata['fix_stream']) {
            $command .= " ".$CONFIG['ffmpeg_fix'];
        }
        $command .= " -bufsize ".($stream['bitrate_kbps']*7)."k -stimeout 60000 -segment_list_flags +live -hls_allow_cache 0".
            " -hls_flags temp_file -hls_time ".$CONFIG['segment_time'].
            " -hls_wrap ".$CONFIG['segment_wrap'];
            //" -hls_flags delete_segments -hls_list_size ".$CONFIG['segment_wrap'];

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
        $saveto = $this->getStoreDir($stream, 'stills')."/".date('Ymd')."-".$time;

        if (file_exists($saveto.".jpg")) {
            return;
        }

        if ($this->cameraOn()) {
            $this->captureImageOn($stream, $saveto.".jpg");
        } else {
            $this->captureImageOff($stream, $saveto.".jpg");
        }

        $this->makeThumb($saveto.".jpg", $saveto."-thumb.jpg", 125);

        $images = $this->getFiles($this->getStoreDir($stream, 'stills')."/*jpg");
        $maximage = 60;
        if (count($images) > $maximage) {
            for ($loop=$maximage ; $loop<$maximage; $loop) {
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
        $images = $this->getFiles($this->getStoreDir($stream, 'stills')."/*thumb.jpg");

        $paths=array();
        $count=0;
        foreach ($images as $image) {
            $path = new stdclass();
            $path->thumb = 'stills/'.$this->camkey."-".$stream['bitrate_kbps'].'/'.basename($image);
            $path->img = str_replace('-thumb.jpg', '.jpg', $path->thumb);
            $path->time = date ("dS F Y, H:i", filemtime($image));
            $paths[] = $path;
            $count++;
            if ($count>=30) break;
        }
        return $paths;
    }

    function getVideoClips() {
        $stream = $this->firstStream();
        $clips = $this->getFiles($this->getStoreDir($stream, 'vclips')."/*");

        $paths=array();
        $count=0;
        foreach ($clips as $clip) {
            $path = new stdclass();
            $b = basename($clip);
            $path->thumb = 'vclips/'.$this->camkey."-".$stream['bitrate_kbps'].'/'.$b.'/thumb.jpg';
            if (!file_exists($path->thumb)) {
                $path->thumb = $this->getChannelMask();
            }
            $path->link = 'clipshow.php?camkey='.$this->camkey."&amp;stream=".$stream['bitrate_kbps']."&amp;clip=".$b;
            //$path->time = date ("dS F Y, H:i", filemtime($clip));
            $path->time = file_get_contents('vclips/'.$this->camkey."-".$stream['bitrate_kbps'].'/'.$b.'/date.txt');
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
        $saveto = $this->getStoreDir($stream, 'vclips')."/".date('Ymd')."-".$time;

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

        shell_exec($CONFIG['ffmpeg']." -loglevel panic -i ".$saveto."/combined.ts -acodec copy -vcodec copy -hls_list_size 0 -hls_time ".$CONFIG['segment_time']." ".$saveto."/clip.m3u8");
        unlink($saveto."/combined.ts");

        $vclips = $this->getStoreDir($stream, 'vclips');
        $maxvclip = 30;
        if (count($vclips) > $maxvclip) {
            for ($loop=$maxvclip; $loop<$maxvclip; $loop) {
                shell_exec("rm -rf ".$vclips[$loop]);
            }
        }
    }

    function stopStream($stream) {
        global $CONFIG;
        $pid = $this->streamRunning($stream);
        if ($pid) {
            shell_exec("kill ".$pid);
        }
    }
}

class CameraNotFoundException extends Exception {

    function __construct($camera) {
        $trace = $this->getTrace();
        $message = "The requested camera '".$camera."' cannot be found on this server.";
        parent::__construct($message);
    }

}