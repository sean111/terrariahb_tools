<?php
//Functions have been split into this file to help with testing and in case I want to use them for something else
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

function logError($error) {
    $string="[".date('m/d/Y H:ia',time())."] $error\n";
    file_put_contents('error.log',$string, FILE_APPEND);
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
    $name=str_replace("%27", "'", $name);
    $nameInfo=pathinfo($name);
    $name=strtolower($nameInfo['filename']);
    $dir=$baseDir.$imageDir;
    $files=glob($dir."*");
    foreach($files as $file) {
        $fInfo=pathinfo($file);
        $fname=strtolower($fInfo['filename']);
        if($fname==$name) {
            return str_replace($baseDir,null,$file);
        }
    }
    logError("Image not found $name");
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
    //$url="http://wiki.terrariaonline.com/$wname?action=render";
    $url = "http://terraria.gamepedia.com/$wname?action=render";
    curl_setopt($cp, CURLOPT_URL, $url);
    $data=curl_exec($cp);
    if(strstr($data, "Unexpected Error") || strstr($data, "unexpected error")) {
        unset($data);
        print "error\n";
        print "\tRetrying $name ";
        loadObject($name);
        return false;
    }
    $data=str_replace(array('&nbsp;','&ndash;'),array(' ','-'),$data);
    $doc=phpQuery::newDocument($data);
    pq('.editsection')->remove();
    pq('div.gallerybox')->remove();
    pq('div.plainlinks')->remove();
    pq('script')->remove();
    pq('#toc')->remove();
    pq('.thumbimage')->remove();
    pq('.internal')->remove();
    pq('object')->remove();
    pq('ul.gallery')->remove();
    pq('#Gallery')->remove();
    //pq('.infobox')->attr('style','width: 85%; font-size:89%; -moz-border-radius: .7em; -webkit-border-radius: .7em; border-radius: .7em;');
    //pq('.craftbox')->attr('style','width: 85%; font-size:89%; -moz-border-radius: .7em; -webkit-border-radius: .7em; border-radius: .7em; border: 1px solid #aaaaaa; padding: 0.2em; margin-bottom:5px;');
    pq('th')->attr('style', '');
    pq('table')->attr('style', '');
    foreach(pq('table') as $table) {
        /*if(pq($table)->attr('class')==null) {
            pq($table)->attr('style','width: 85%; font-size:89%; -moz-border-radius: .7em; -webkit-border-radius: .7em; border-radius: .7em; border: 1px solid #aaaaaa; padding: 0.2em; margin-bottom:5px;');
        }*/
        $prevTable=null;
        foreach(pq($table)->find('table') as $subTable) {
            if(!$prevTable) {
                $prevTable=pq($table);
            }
            //pq($subTable)->attr('style','width: 85%; font-size:89%; -moz-border-radius: .7em; -webkit-border-radius: .7em; border-radius: .7em; border: 1px solid #aaaaaa; padding: 0.2em; margin-bottom:5px;');
            pq($subTable)->attr('align','center');
            //pq($subTable)->find('th')->attr('style', 'padding: 0.2em; background: #E4F0F7; color: #063B5E; text-align: center;');
            //pq($subTable)->find('th')->attr('style', 'padding: 0.2em; text-align: center;');
            pq($subTable)->insertAfter($prevTable);
            $prevTable=pq($subTable);
        }
        //table-layout: fixed; word-wrap: break-word;
    }
    //pq('table table')->wrap('<table><tr><td></td></tr></table>');
    foreach(pq('a') as $link) {
        $title=pq($link)->attr('title');
        if(in_array($title, $objects)) {
            pq($link)->attr('href',cleanString($title).".html");
            pq($link)->wrap("<span class='link' />");
        }
        else {
            pq($link)->removeAttr('href');
        }
    }
    foreach(pq('img') as $img) {
        $iSrc=pq($img)->attr('src');
        $alt=pq($img)->attr('alt');
        if($alt=='Anomaly.png' || $alt=='Bug.png') {
            pq($img)->attr('name',"img/$alt");
            pq($img)->removeAttr('src');
        }
        else if($alt == 'Spectral Armor.png') {
            pq($img)->attr('name', 'img/Spectral_Armor_Chest.png');
            pq($img)->removeAttr('src');
        }
        else {
            $imgSrc=getImage(basename($iSrc));
            if(!$imgSrc) {
                $imgSrc=getImage($alt);
            }
            if(!empty($imgSrc)) {
                pq($img)->attr('name',$imgSrc);
            }
            pq($img)->attr('src','');
            pq($img)->attr('class', 'timg');
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
