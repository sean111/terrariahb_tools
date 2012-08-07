<?php
require_once 'config.php';
require_once 'phpQuery.php';
require_once 'functions.php';
$img="/images/thumb/5/5c/Black_Slime.png/20px-Black_Slime.png";
$alt='Black Slime.png';
$img=getImage(basename($img));
if(!$img) {
    $img=getImage($alt);
}
print "IMG: $img\n";
print getImage(basename($img))."\n";
print getImage($alt)."\n";
?>