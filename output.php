<?php
include 'config.php';
$start=microtime(true);
$db=new mysqli($dbHost,$dbUser,$dbPass,$dbName);
$template=$templateFile;
$outFile=$outputFile;

//ITEMS
$sql="SELECT c.name as cat, c.order, i.name, i.img, i.id as itemid, i.notes, i.ctool, i.camt FROM item_cats c LEFT OUTER JOIN items i ON c.id=i.cat ORDER BY `order`, i.id";
$sql=$db->query($sql);

$cCat=null;
$iListString="";
$iString="";
$catString="";
while($item=$sql->fetch_array()) {
    if($item['cat']!=$cCat) {
        //New Cat
        if($cCat!=null) {
            $iListString.="</ul>\n</div>\n</div>\n";
        }
        $tc=strtolower($item['cat']);
        $tc=str_replace(" ","_",$tc);
        $catString.="<li><a href=\"#$tc\">$item[cat]</a></li>\n";
        $iListString.="<div id='$tc' data-role='page'>\n<div data-role='header'><h1>$item[cat]</h1></div>\n<div data-role='content'>\n<ul data-role='listview' data-inset='false' data-filter='true'>\n";
        $cCat=$item['cat'];
    }
	$item['nlink']=cleanString($item['name']);
    $iListString.="<li><a href=\"#$item[nlink]\">";
    if(!empty($item['img'])) {
        $iListString.="<img src=\"$item[img]\" alt=\"$item[name]\" width='20' height='20' class='ui-li-icon' />";
    }
    
    $iListString.="$item[name]</a></li>\n";
    item($item);
}
$iListString.="</ul>\n</div>\n</div>\n"; //Closes out the last cat

//MONSTERS
$sql="SELECT c.name as cat, m.name, m.img, m.id as monsterid, m.notes FROM `monster_cats` c INNER JOIN monsters m ON c.id=m.catid";
$sql=$db->query($sql);
$cCat=null;
$mListString="";
$mString="";
$mcatString="";
while($monster=$sql->fetch_array()) {
    if($monster['cat']!=$cCat) {
        if($cCat!=null) {
            $mListString.="</ul>\n</div>\n</div>\n";            
        }
        $tc=strtolower($monster['cat']);
        $tc=str_replace(" ","_",$tc);
        $mCatString.="<li><a href=\"#$tc\">$monster[cat]</a></li>\n";
        $mListString.="<div id='$tc' data-role='page'>\n<div data-role='header'><h1>$monster[cat]</h1></div>\n<div data-role='content'>\n<ul data-role='listview' data-inset='false' data-filter='true'>\n";
        $cCat=$monster['cat'];
    }
	$monster['nlink']=cleanString($monster['name']);
    $mListString.="<li><a href=\"#$monster[nlink]\">";
    if(!empty($monster['img'])) {
        $mListString.="<img src=\"$monster[img]\" alt=\"$monster[name]\" width='20' height='20' class='ui-li-icon' />";
    }
    
    $mListString.="$monster[name]</a></li>\n";
    monster($monster);
}
$mListString.="</ul>\n</div>\n</div>\n";

//ENVIROMENTS
$eCatString="";
$eString="";
foreach(glob('Enviroments/*.html') as $filename) {
    $name=str_replace('.html','',$filename);
    $name=str_replace('Enviroments/','',$name);
    $sname=str_replace(' ','',$name);
    $eCatString.="<li><a href=\"#$sname\">$name</a></li>";
    $eString.="<div id='$sname' data-role='page'>";
    $eString.="<div data-role='header'><h1>$name</h1></div>";
    $eString.="<div data-role='content'>";
    $eString.=preg_replace('/\[\[(.+)\]\]/e',"'<div class=\'link\'><a href=#'.cleanString('\\1').'>\\1</a></div>'",file_get_contents($filename));
    $eString.="</div></div>";
}

//NPCs
$nCatString="";
$nString="";
foreach(glob('NPCs/*.html') as $filename) {
    $name=str_replace('.html','',$filename);
    $name=str_replace('NPCs/','',$name);
    $sname=str_replace(' ','',$name);
    $nCatString.="<li><a href=\"#$sname\">$name</a></li>";
    $nString.="<div id='$sname' data-role='page'>";
    $nString.="<div data-role='header'><h1>$name</h1></div>";
    $nString.="<div data-role='content'>";
    $nString.=preg_replace('/\[\[(.+)\]\]/e',"'<div class=\'link\'><a href=#'.cleanString('\\1').'>\\1</a></div>'",file_get_contents($filename));
    $nString.="</div></div>";
}

$htmlData=file_get_contents($template);

$vnData=file_get_contents('version_notes.html');
//$vnData=nl2br($vnData);

$htmlData=str_replace("{VERSIONNOTES}",$vnData,$htmlData);
$htmlData=str_replace("{CATLIST}",$catString,$htmlData);
$htmlData=str_replace("{ITEMLIST}",$iListString,$htmlData);
$htmlData=str_replace("{ITEMS}",$iString,$htmlData);
$htmlData=str_replace("{MCATLIST}",$mCatString,$htmlData);
$htmlData=str_replace("{MONSTERLIST}",$mListString,$htmlData);
$htmlData=str_replace("{MONSTERS}",$mString,$htmlData);
$htmlData=str_replace("{ECATLIST}",$eCatString,$htmlData);
$htmlData=str_replace("{ENVS}",$eString,$htmlData);
$htmlData=str_replace("{NPCLIST}",$nCatString,$htmlData);
$htmlData=str_replace("{NPCS}",$nString,$htmlData);

file_put_contents($outFile,$htmlData);

$ttime=microtime(true)-$start;

print "Output generated in ".round($ttime,4)."  seconds\n";

function item($item) {
    global $iString;
    global $db;    
    $iString.="<div id=\"$item[nlink]\" data-role='page'>\n";
    $iString.="<div data-role='header'><h1>$item[name]</h1></div>\n";
    $iString.="<div data-role='content'>\n";
    if($item['img']) {
        $iString.="<img src=\"$item[img]\" alt=\"$item[name]\" class='item_image'/>\n";
    }
        $iString.="<p>$item[notes]</p>\n";
        $sql=$db->query("SELECT name, value FROM item_stats WHERE itemid=$item[itemid]");
        if($sql->num_rows>0) {
            $iString.="<table id='item_stats'><tr><th colspan='2'>Tool Stats</th></tr>\n";
            while($stats=$sql->fetch_array()) {
                $value=str_replace(array('{{cc','{{sc','{{gc'), array(' Copper Coin(s)',' Silver Coin(s)',' Gold Coin(s)'), $stats['value']);
                $iString.="<tr><td style='font-weight: bold;'>$stats[name]</td><td>$value</td></tr>\n";
            }
            $iString.="</table>\n";
        }

    $sql="SELECT ii.name, ii.amt, i.img FROM item_ingredients ii LEFT OUTER JOIN items i ON ii.name=i.name WHERE itemid=$item[itemid]";
    $sql=$db->query($sql);
    if($sql->num_rows>0) {
        $iString.="<br /><table id='item_ingredients'>";
        $iString.="<tr><th colspan='2'>Crafting</th></tr>";
        $iString.="<tr><th colspan='2'>Tool</th></tr>";
        $iString.="<tr style='text-align:center;'><td colspan='2'>";
        if(empty($item['ctool'])) {
            $iString.='Hand';
        }
        else {
            $isql="SELECT img FROM items WHERE name='$item[ctool]'";
            $isql=$db->query($isql);
            $ctool=$isql->fetch_array();
            if($ctool['img']) {
                $iString.="<img src=\"$ctool[img]\" alt=\"$item[ctool]\" />";
            }
            $iString.="$item[ctool]";
        }
        $iString.='</td></tr>';
        $istring.="<tr><th colspan='2'>Ingredients</th></tr>";
        $iString.="<tr><th>Item</th><th>Amount</th></tr>";
        while($ing=$sql->fetch_array()) {
            $iString.="<tr><td>";
            if(!empty($ing['img'])) {
                $iString.="<img src=\"$ing[img]\" alt=\"$ing[name]\" />";
            }
            $iString.="&nbsp;&nbsp;<div class='link'><a href='#".cleanString($ing['name'])."'>$ing[name]</a></div></td><td>$ing[amt]</td></tr>";
        }
        $iString.="<tr><td style='font-weight: bold;'>Result</td><td>x$item[camt]</td></tr>";
        $iString.="</table>";
    }
    $iString.="</div>\n</div>\n";
}

function monster($monster) {
    global $mString;
    global $db;
    $mString.="<div id=\"$monster[nlink]\" data-role='page'>\n";
    $mString.="<div data-role='header'><h1>$monster[name]</h1></div>\n";
    $mString.="<div data-role='content'>\n";
    if(!empty($monster['img'])) {
        $mString.="<img src=\"$monster[img]\" alt=\"$monstername]\" class='item_image'/>\n";
    }
    $mString.="<p>".preg_replace('/\[\[(.+)\]\]/e',"'<div class=\'link\'><a href=#'.cleanString('\\1').'>\\1</a></div>'",nl2br($monster['notes']))."</p>\n";
    $sql=$db->query("SELECT name, value FROM monster_stats WHERE mid=$monster[monsterid]");
    if($sql->num_rows>0) {
        $mString.="<table id='monster_stats'><tr><th colspan='2'>Stats</th></tr>\n";
        while($stats=$sql->fetch_array()) {
            $mString.="<tr><td style='font-weight: bold;'>$stats[name]</td><td>$stats[value]</td></tr>";
        }
        $mString.="</table>";
    }
    $mString.="</div>\n</div>\n";

}

function cleanString($string) {
    $tmp=str_replace("'",'',$string);
    $tmp=str_replace(" ",'',$tmp);
    return $tmp;
}
?>
