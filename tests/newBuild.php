<?php
require_once 'config.php';
require_once 'phpQuery.php';
$templateFile='/srv/www/droid/thb/template.html';
$outputFile='/srv/www/droid/thb/index.html';
//Query: SELECT m.id, m.name, m.link as mlink, o.name, o.img, o.link as olink, c.id as cid, c.name FROM menu m LEFT OUTER JOIN objects o ON m.id=o.mid LEFT OUTER JOIN cats c ON o.cid=c.id;


?>
