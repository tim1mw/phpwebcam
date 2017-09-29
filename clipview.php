<?php include('setup.php'); 

$clips = $camera->getVideoClips();

foreach ($clips as $clip) {
    echo "<a href='".$clip->link."' target='_blank'><img src='".$clip->thumb."' class='thumbnail' alt='".$clip->time."' title='".$clip->time."'/></a>\n";
}