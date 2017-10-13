<?php include('setup.php'); 

$clips = $camera->getVideoClips();

foreach ($clips as $clip) {
    echo "<div class='thumbnailcont'>". 
         "<a href='".$clip->link."' target='_blank' class='thumbnailtext'>".
         "<div style='background-image:url(".$clip->thumb.")' class='thumbnail' alt='".$clip->time."' title='".$clip->time."'/>".
         "<div class='thumbinner'><div class='thumbmask'>".str_replace(',', '<br />', $clip->time)."</div></div>".
         "</div></a></div>\n";
}