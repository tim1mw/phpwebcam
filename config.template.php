<?php

$CONFIG = [
    'ffmpeg' => '',
    'ffmpeg_fix' => '',
    'ffmpeg_encode' => '',
    'log_dir' => '',
    'log_level' => 'debug',
    'segment_time' => 5,
    'segment_wrap' => 180
];

$CAMERAS = [

    'gorsaf-llw' => array(
        'name' => '',
        'start' => '',
        'finish' => '',
        'maintenance' => false,
        'camera_base_url' => '',
        'rtsp_transport' => 'tcp',
        'fix_stream' => true,
        'fix_framerate' => true,
        'poster_image' => '',
        'channel_mask' => '',
        'camera_streams' => array(
            '0' => array(
                'url_part' => '',
                'bitrate_kbps' => ,
                'frame_rate' => 
            ),
        ),
        'still_times' => array(
            '0' => ,
        ),
        'vclip_times' => array(
            '0' => ,
        )
    )

];
