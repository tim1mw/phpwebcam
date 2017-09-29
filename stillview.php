<?php include('setup.php'); 
$images = $camera->getThumbnails();

foreach ($images as $image) {
    echo "<a href='".$image->img."' target='_blank'><img src='".$image->thumb."' class='thumbnail' alt='".$image->time."' title='".$image->time."'/></a>\n";
}