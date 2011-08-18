<?php
require_once '../config.php';
require_once 'phpQuery.php';
$templateFile='template.html';
$outputFile='index.html';   

$db=new mysqli($dbHost, $dbUser, $dbPass, $dbName);
$sql=$db->query("SELECT m.id, m.name as name, m.link as mlink, o.name as oname, o.img, o.link as olink, c.id as cid, c.name as cname FROM menu m LEFT OUTER JOIN objects o ON m.id=o.mid LEFT OUTER JOIN cats c ON o.cid=c.id");
$cMenu=null;
$cCat=null;
$menu="";
$cArray=array();
$data="";
while($tmp=$sql->fetch_array()) {
    if($tmp['id']!=$cMenu) {
        if(!empty($tmp['mlink'])) {
            $link=$tmp['mlink'];
        }
        else {
            $link=strtolower(cleanString($tmp['name']));
        }
        $menu.="<li><a href=#$link>$tmp[name]</a></li>\n";
        unset($link);
        if(!empty($cCat)) {
            for($x=0; $x<sizeof($cArray); $x++) {
                
            }
        }
    }    
}

print $menu;

function cleanString($string) {
    $tmp=str_replace("'",'',$string);
    $tmp=str_replace("`",'',$tmp);
    $tmp=str_replace(" ",'',$tmp);
    return $tmp;
}
?>
