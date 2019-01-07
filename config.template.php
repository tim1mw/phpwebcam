<?php

$CONFIG = [
    'hostname' => '',
    'adminpass' => '',
    'ffmpeg' => '',
    'ffmpeg_fix' => '',
    'ffmpeg_encode' => '',
    'log_dir' => '',
    'log_level' => 'debug',
    'segment_time' => 5,
    'segment_wrap' => 180,
    'max_fail_count' => 10
];

$CAMERAS = [

    'gorsaf-llw' => array(
        'name' => '',
        'start' => '',
        'finish' => '',
        'ping_test' => '',
        'stream_type' => '', 
        'camera_base_url' => '',
        'username' => '',
        'password' => '',
        'fix_stream' => true,
        'poster_image' => '',
        'channel_mask' => '',
        'camera_streams' => array(
            '0' => array(
                'url_part' => '',
                'bitrate_kbps' => ,
                'frame_rate' => ,
                'rtsp_params' => ''
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
