<?php
require_once 'config.php';
require_once 'phpQuery.php';
require_once 'functions.php';
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

print "Cleaning out html files";
exec("rm $baseDir/*.html");

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
$objTemplate=file_get_contents($objTemplateFile);

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
$tfdata=file_get_contents($templateFile);
$tfdata=str_replace("{MAINMENU}", $mmSource, $tfdata);
$tfdata=str_replace("{CATS}", $cSource, $tfdata);
//$tfdata=str_replace("{OBJECTS}", $objectSource, $tfdata);
$tfdata=str_replace("{VERSIONNOTES}", $versionNotes, $tfdata);
file_put_contents($baseDir.$indexFile, $tfdata);
?>
