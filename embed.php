<?php
require_once("setup.php");
header('Content-type: text/javascript');
?>

if (document.documentElement.clientWidth > 840) {

    document.writeln('<iframe style="text-align: center; border: 0px; padding: 0px;"'+
       ' src="<?php echo $CONFIG['hostname']?>/camview.php?camkey=<?php echo $camera->getCamKey()?>" width="825" height="500" allowfullscreen="allowfullscreen"></iframe>');
} else {

    document.writeln('<div style="position:relative;text-align:center;">'+
        '<a href="<?php echo $CONFIG['hostname']?>/camview.php?camkey=<?php echo $camera->getCamKey()?>" target="_blank">'+
        '<img src="<?php echo $CONFIG['hostname']?>/<?php echo $camera->getPosterImage(); ?>" style="width:90%" alt="Click to view the webcam" title="Click to view the webcam"/>'+
        '<div style="position:absolute;top:30%;left:10%;width:80%;text-align:center;opacity:0.7;color:white;background-color:black;font-family: sans-serif;">Tap or click here to view the webcam</div></a></div>');

}
