<?php include('setup.php'); 
$images = $camera->getThumbnails();

foreach ($images as $image) {
    echo "<div class='thumbnailcont'>". 
         "<a href='".$image->img."' target='_blank' class='thumbnailtext'>".
         "<div style='background-image:url(".$image->thumb.")' class='thumbnail' alt='".$image->time."' title='".$image->time."'/>".
         "<div class='thumbinner'><div class='thumbmask'>".str_replace(',', '<br />', $image->time)."</div></div>".
         "</div></a></div>\n";
}