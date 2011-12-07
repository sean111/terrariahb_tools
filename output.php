<?php
require_once 'config.php';
require_once 'phpQuery.php';

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
//$objectSource='';
$loadedObjects=array();
$objTemplate=file_get_contents($baseDir.'objTemplate.html');

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
            print "\t$obj[name] ";
            if(!empty($obj['link'])) {
                $link=$obj['link'];
            }
            else {
                $link=cleanString($obj['name']);
            }
            $img=getImage($obj['name']);

            $src="<li class='object' name='$link'><a href='$link.html'>";
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
            print "- done!\n";     
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

print "Version Notes\n";
$versionNotes=file_get_contents('versionNotes.html');
$versionNotes=nl2br($versionNotes);

print "Creating $indexFile from $templateFile\n";
$tfdata=file_get_contents($baseDir.$templateFile);
$tfdata=str_replace("{MAINMENU}", $mmSource, $tfdata);
$tfdata=str_replace("{CATS}", $cSource, $tfdata);
//$tfdata=str_replace("{OBJECTS}", $objectSource, $tfdata);
$tfdata=str_replace("{VERSIONNOTES}", $versionNotes, $tfdata);
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
    //TODO: Use regex instead of explode
    global $imageDir, $baseDir;
    $name=str_replace(" ", "_", $name);    
    $dir=$baseDir.$imageDir."/";
    $files=glob($dir."*");
    foreach($files as $file) {
        $tmp=explode(".",$file);
        $tmp=str_replace($dir,null,$tmp[0]);
        if(strtolower(str_replace("'","",$name))==strtolower(str_replace("'","",$tmp))) {                        
            $file=str_replace($dir, $imageDir, $file);
            return $file;
        }    
    }
    return false;
}

/**
 * Loads the page and parses the data for a supplied object
 *
 * This function will load the wiki page for the obkect than parse it using phpQuery to modify
 * the data to fit into the application and update images/links
 *
 * @param string Name of the object
 **/
function loadObject($name) {
    global $cp, $objects, $objTemplate, $baseDir;    
    $objectSource="";
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
    pq('.thumbimage')->remove();
    pq('.internal')->remove();
    pq('.infobox')->attr('style','width: 85%; font-size:89%; -moz-border-radius: .7em; -webkit-border-radius: .7em; border-radius: .7em;');
    pq('.craftbox')->attr('style','width: 85%; font-size:89%; -moz-border-radius: .7em; -webkit-border-radius: .7em; border-radius: .7em; border: 1px solid #aaaaaa; padding: 0.2em; margin-bottom:5px;');
    foreach(pq('table') as $table) {
        /*if(pq($table)->attr('class')==null) {
            pq($table)->attr('style','width: 85%; font-size:89%; -moz-border-radius: .7em; -webkit-border-radius: .7em; border-radius: .7em; border: 1px solid #aaaaaa; padding: 0.2em; margin-bottom:5px;');
        }*/       
        foreach(pq($table)->find('table') as $subTable) {
            pq($subTable)->attr('style','width: 85%; font-size:89%; -moz-border-radius: .7em; -webkit-border-radius: .7em; border-radius: .7em; border: 1px solid #aaaaaa; padding: 0.2em; margin-bottom:5px; background: #f9f9f9;');
            pq($subTable)->attr('align','center');
            pq($subTable)->find('th')->attr('style', 'padding: 0.2em; background: #E4F0F7; color: #063B5E; text-align: center;');
            pq($subTable)->insertAfter(pq($table));
        }
        //table-layout: fixed; word-wrap: break-word;        
    }
    //pq('table table')->wrap('<table><tr><td></td></tr></table>');
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
        $iSrc=pq($img)->attr('src');
        //$alt=str_replace(" ","_", $alt);        
        if(substr($alt,0,4)!="img/") {
            $info=pathinfo($iSrc);
            $iSrc=basename($iSrc,'.'.$info['extension']);
            //print substr($alt,0,4)."\n";
            $iSrc=str_replace("-tiles","",$iSrc);
            //print "Looking for: $alt\n";
            $imgSrc=getImage($iSrc);
            if(!empty($img)) {
                pq($img)->attr('name',$imgSrc);
            }
            //pq($img)->attr('src','img/'.$alt);
            pq($img)->removeAttr('src');        
        }
    }
    $doc=preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $doc);
    $objData=$objTemplate;
    $objectSource.="<div id='".cleanString($name)."' data-role='page'>\n";
    $objectSource.="<div data-role='header'><h1>$name</h1></div>\n";
    $objectSource.="<div data-role='content'>\n";
    $objectSource.=$doc;
    $objectSource.="</div>\n</div>\n";
    $objData=str_replace("{DATA}", $objectSource, $objData);
    file_put_contents($baseDir.cleanString($name).'.html',$objData);
    unset($doc, $data, $objData);
}
?>
