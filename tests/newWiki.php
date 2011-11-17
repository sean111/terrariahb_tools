<?php
require_once 'config.php';
require_once 'phpQuery.php';

$webDir='/srv/www/droid/terrariahb/';

$db=new mysqli($dbHost, $dbUser, $dbPass, $dbName);
//First get all objects into an array so we know what can be linked
$objects=array();
$sql=$db->query("SELECT name FROM test");
while($tmp=$sql->fetch_array()) {
    $objects[]=$tmp['name'];
}

$cp=curl_init();
$header=array(
    "Content-Type: text/html; charset\"utf-8\"",
    "Accept-Charset: utf-8"  
);
curl_setopt($cp, CURLOPT_HTTPHEADER, $header);
curl_setopt($cp, CURLOPT_HEADER, 0);
curl_setopt($cp, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($cp, CURLOPT_ENCODING,'gzip');

for($x=0; $x<sizeof($objects); $x++) {
    $obj=$objects[$x];
    $wobj=str_replace(" ","_",$obj);
    $url="http://wiki.terrariaonline.com/$wobj?action=render";
    curl_setopt($cp, CURLOPT_URL, $url);
    $data=curl_exec($cp);
    $data=str_replace(array('&nbsp;','&ndash;'),array(' ','-'),$data);
    $doc=phpQuery::newDocument($data);
    pq('.editsection')->remove();
    pq('div.gallerybox')->remove();
    pq('div.plainlinks')->remove();
    foreach(pq('a') as $link) {
        $title=pq($link)->attr('title');
        if(in_array($title, $objects)) {
            pq($link)->attr('href','#'.cleanString($title));
            pq($link)->wrap("<span class='link' />");
        }
        else {
            pq($link)->removeAttr('href');
        }
    }
    foreach(pq('img') as $img) {
        $alt=pq($img)->attr('alt');
        $alt=str_replace(" ","_", $alt);        
        pq($img)->attr('name','img/'.$alt);
        pq($img)->removeAttr('src');        
    } 
    //$doc=xml_character_encode($doc);   
    $doc=preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $doc);    
    print $doc; 
    break;

}

function cleanString($string) {
    $tmp=str_replace("'",'',$string);
    $tmp=str_replace("`",'',$tmp);
    $tmp=str_replace(" ",'',$tmp);
    return $tmp;
}
?>
