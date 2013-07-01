<?php
//Temp config (move to config.php or reuse it)
$dbName = 'terrariahb';
$dbHost = 'localhost';
$dbUser = 'seanb';
$dbPass = 'w00tw00t';

$dir = '/srv/www/projects/droid/thb/';

$imgDir = $dir.'img/';

//Clear out old files
exec("rm $dir/objects/menu/*.html");
unlink($dir."/js/data.js");

//fixImageNames();


$db = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

$sql = $db->query("SELECT m.name as menu, c.name as cat, o.name as name FROM objects o INNER JOIN menu m ON o.mid = m.id LEFT OUTER JOIN cats c ON o.cid = c.id ORDER BY mid, cid, name");

$menu = null;
$menuFile = null;
$cat = null;
$catFile = null;
$hasCat = false;

$objects = array();

$template = "
<div id='mainMenu' data-role='page'>
        <div data-role='header' data-nobackbtn='true'>
            <h1>{header}</h1>
        </div>
        <div data-role='content'>
            <ul data-role='listview' data-inset='true'>
                {menu}
            </ul>
        </div>
    </div>
";

while($row = $sql->fetch_array()) {
    print $row['menu'].' - '.$row['cat'].' - '.$row['name']."\n";
    if($row['menu'] != $menu) {
        if($menu) {
            if($hasCat) {
                //output to cat file
                $catHtml = str_replace('{menu}', $catHtml, $template);
                $catHtml = str_replace('{header}', $menu, $catHtml);
                file_put_contents($menuFile, $catHtml);

                $html = str_replace('{menu}', $html, $template);
                $html = str_replace('{header}', $cat, $html);
                file_put_contents($catFile, $html);
            }
            else {
                //output to menu file
                $html = str_replace('{menu}', $html, $template);
                $html = str_replace('{header}', $cat, $html);
                file_put_contents($menuFile, $html);
            }
        }
        $menuFile = $dir.'/objects/menu/'.cleanString($row['menu']).".html";
        $hasCat = empty($row['cat']) ? false : true;
        $catHtml = null;
        $html = null;
        $menu = $row['menu'];
        $cat = null;
    }

    if($hasCat) {
        if($row['cat'] != $cat) {
            if($cat) {
                $html = str_replace('{menu}', $html, $template);
                $html = str_replace('{header}', $cat, $html);
                file_put_contents($catFile, $html);
            }
            $catFile = $dir."/objects/menu/".cleanString($row['cat']).".html";
            $html = null;
            $cat = $row['cat'];
            $catHtml .= "<li><a href='".cleanString($row['cat']).".html'>$row[cat]</a></li>";
        }
    }

    $link = 'objects/'.cleanString($menu).'/'.cleanString($row['name']).'.html';
    $img = getImg($row['name']);
    $html .= "<li><a href='../../$link'>";
    if($img) {
        $html .="<img src='../../$img' class='ui-li-icon ul-li-thumb' height='15px' width='20px'/>";
    }
    $html .= "$row[name]</a></li>\n";
    $objects[]=array(
        'name' => $row['name'],
        'link' => $link,
        'img' => $img
    );
}

//Clear out last menu / cat
if($menu) {
    if($hasCat) {
        //output to cat file
        $catHtml = str_replace('{menu}', $catHtml, $template);
        $catHtml = str_replace('{header}', $menu, $catHtml);
        file_put_contents($menuFile, $catHtml);

        $html = str_replace('{menu}', $html, $template);
        $html = str_replace('{header}', $cat, $html);
        file_put_contents($catFile, $html);
    }
    else {
        //output to menu file
        $html = str_replace('{menu}', $html, $template);
        $html = str_replace('{header}', $cat, $html);
        file_put_contents($menuFile, $html);
    }
}

$json = json_encode($objects);
$json = 'var objects = '.$json;
file_put_contents($dir."/js/data.js", $json);

function cleanString($string) {
    $string = strtolower($string);
    $string = str_replace(array(' ', "'", '(', ')', '_'), '', $string);
    return $string;
}

function getImg($object) {
    global $imgDir;
    $name = cleanString($object);
    $file=glob($imgDir.$name.".*");
    if($file[0]) {
        return 'img/'.basename($file[0]);
    }
    return null;

}

function fixImageNames() {
    global $dir;
    foreach(glob("$dir/img/*") as $file) {
        $fileName = basename($file);
        print "Old File: $fileName";
        $fileName = cleanString($fileName);
        print " | New File: $fileName\n";
        $data=file_get_contents($file);
        file_put_contents("$dir/img/$fileName", $data);
        unlink($file);
    }
}
?>