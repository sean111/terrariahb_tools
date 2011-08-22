<?php
require_once '../config.php';
require_once 'phpQuery.php';

$baseDir='/srv/www/droid/thb/';
$templateFile='template.html';
$indexFile='index.html';
$imageDir='img/';

//Curl Init
$cp=curl_init();
$header=array(
    "Content-Type: text/html; charset\"utf-8\"",
    "Accept-Charset: utf-8"  
);
curl_setopt($cp, CURLOPT_HTTPHEADER, $header);
curl_setopt($cp, CURLOPT_HEADER, 0);
curl_setopt($cp, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($cp, CURLOPT_ENCODING,'gzip');

$db=new mysqli($dbHost, $dbUser, $dbPass, $dbName);

$objects=array();
$sql=$db->query('SELECT name FROM objects');
while($tmp=$sql->fetch_array()) {
    $objects[]=$tmp['name'];
}

$sql=$db->query("SELECT id, name, link FROM menu ORDER BY `order`");
$mmSource='';
$cSource='';
$objectSource='';
$loadedObjects=array();

while($mmenu=$sql->fetch_array()) {
    $stuff=array();
    if(!empty($mmenu['link'])) {
        $link=$mmenu['link'];
        $isLinked=true;
    }
    else {
        $link=cleanString($mmenu['name']);
        $isLinked=false;
    }
    print "Menu Item: $mmenu[name]\n";
    $mmSource.="<li><a href='#$link'>$mmenu[name]</a></li>";
    if(!$isLinked) {  
        $oSql="SELECT o.name, link, c.name as cat FROM objects o LEFT OUTER JOIN cats c ON o.cid=c.id WHERE o.mid=$mmenu[id] ORDER BY c.`order`, c.id, o.name";
        //print $oSql."\n";
        $oSql=$db->query($oSql);        
        while($obj=$oSql->fetch_array()) {
            print "\t$obj[name]\n";
            if(!empty($obj['link'])) {
                $link=$obj['link'];
            }
            else {
                $link=cleanString($obj['name']);
            }
            $img=getImage($obj['name']);

            $src="<li><a href='#$link'>";
            if(!empty($img)) {
                $src.="<img name=\"$img\" alt=\"$obj[name]\" width='20' height='20' class='ui-li-icon' />";
            }
            $src.=$obj['name']."</a></li>\n";
            if($obj['cat']) {                
                $stuff[$obj['cat']][]=$src;
            }
            else {
                $stuff[]=$src;
            }
            //Get Object Data Here
            if(!in_array($obj['name'], $loadedObjects)) {
                loadObject($obj['name']);
                $loadedObjects[]=$obj['name'];
            }            
        }
        $tSrc=null;
        $cSource.="<div id='".cleanString($mmenu['name'])."' data-role='page'>\n";
        $cSource.="<div data-role='header'><h1>$mmenu[name]</h1></div>\n";
        $cSource.="<div data-role='content'>\n";
        $cSource.="<ul data-role='listview' data-inset='true'>\n";
        foreach($stuff as $key => $val) {            
            if(is_array($val)) {
                $cSource.="<li><a href='#".cleanString($key)."'>$key</a></li>\n";                
                $tSrc.="<div id='".cleanString($key)."' data-role='page'>\n";
                $tSrc.="<div data-role='header'><h1>$key</h1></div>\n";
                $tSrc.="<div data-role='content'>\n";
                $tSrc.="<ul data-role='listview' data-inset='true'>\n";
                foreach($val as $vObj) {
                    $tSrc.=$vObj;
                }
                $tSrc.="</ul>\n</div>\n</div>\n";
            }                        
            else {
                $cSource.=$val;
            }
        }
        $cSource.="</ul>\n</div>\n</div>\n";
        if($tSrc) {
            $cSource.=$tSrc;
        }     
    }
}

$tfdata=file_get_contents($baseDir.$templateFile);
$tfdata=str_replace("{MAINMENU}", $mmSource, $tfdata);
$tfdata=str_replace("{CATS}", $cSource, $tfdata);
$tfdata=str_replace("{OBJECTS}", $objectSource, $tfdata);
file_put_contents($baseDir.$indexFile, $tfdata);

/**
 * Cleans out certain characters from a string
 *
 * This function is used when cleaning up special characters I don't want in fields like "id".
 *
 * @param string $string the The target string to clean up
 **/
function cleanString($string) {
    $tmp=str_replace("'",'',$string);
    $tmp=str_replace("`",'',$tmp);
    $tmp=str_replace(" ",'',$tmp);
    return $tmp;
}

/**
 * Checks the img directory for the correct image
 *
 * This will check the imageDir directory for png and gif images for the given name.
 * Spaces are also replaced with "_" to keep up the names from the wiki.
 *
 * @param string $name The name of the object
 **/ 
function getImage($name) {
    global $imageDir, $baseDir;
    $name=str_replace(" ", "_", $name);
    $imgFile=$imageDir.$name;
    //print "Looking for $imgFile\n";
    if(file_exists($baseDir.$imgFile.".png")) {
        return $imgFile.".png";
    }
    else if(file_exists($baseDir.$imgFile.".gif")) {
        return $imgFile.".gif";
    }
    else {
        return null;
    }
}

function loadObject($name) {
    global $cp, $objectSource, $objects;    
    $wname=str_replace(" ", "_", $name);
    $url="http://wiki.terrariaonline.com/$wname?action=render";
    curl_setopt($cp, CURLOPT_URL, $url);
    $data=curl_exec($cp);
    $data=str_replace(array('&nbsp;','&ndash;'),array(' ','-'),$data);
    $doc=phpQuery::newDocument($data);
    pq('.editsection')->remove();
    pq('div.gallerybox')->remove();
    pq('div.plainlinks')->remove();
    pq('script')->remove();
    pq('#toc')->remove();
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
        //$alt=str_replace(" ","_", $alt);        
        if(substr($alt,0,4)!="img/") {
            //print substr($alt,0,4)."\n";
            $alt=str_replace("-tiles","",$alt);
            $alt=str_replace(".png", '', $alt);
            $alt=str_replace(".gif", '', $alt);
            //print "Looking for: $alt\n";
            $imgSrc=getImage($alt);
            if(!empty($img)) {
                pq($img)->attr('name',$imgSrc);
            }
            //pq($img)->attr('src','img/'.$alt);
            pq($img)->removeAttr('src');        
        }
    }
    $doc=preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $doc);
    $objectSource.="<div id='".cleanString($name)."' data-role='page'>\n";
    $objectSource.="<div data-role='header'><h1>$name</h1></div>\n";
    $objectSource.="<div data-role='content'>\n";
    $objectSource.=$doc;
    $objectSource.="</div>\n</div>\n";
    unset($doc, $data);
}
?>
