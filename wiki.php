<?php
include 'config.php';
$cp=curl_init();
curl_setopt($cp, CURLOPT_HEADER, 0);
curl_setopt($cp, CURLOPT_RETURNTRANSFER, 1);
$urlTemplate="http://wiki.terrariaonline.com/api.php?format=xml&action=query&titles={OBJECT}&prop=revisions&rvprop=content";
$db=new mysqli($dbHost,$dbUser,$dbPass,$dbName);
if($db->error) {
	die($db->error."\n");
}
//$db->query("TRUNCATE TABLE item_stats");
//$db->query("TRUNCATE TABLE item_ingredients");
//ITEMS!
if(!opendir('Items/')) {
	mkdir('Items');
}
exec("rm Items/*");
$sql="SELECT id, name FROM items";
$sql=$db->query($sql);
while($item=$sql->fetch_array()) {
	$name=str_replace(" ","_",$item['name']);
	$url=str_replace("{OBJECT}",$name,$urlTemplate);
	curl_setopt($cp, CURLOPT_URL, $url);
	$data=curl_exec($cp);
	file_put_contents("Items/$item[name]",$data);
	$xml=new SimpleXMLElement($data);
	$rev=$xml->query->pages->page->revisions->rev;
	preg_match('/{{item infobox[^}]+}}/',$rev,$stats);
	$stats=$stats[0];
	$stats=str_replace("{{item infobox","",$stats);
	$stats=str_replace("}}", "", $stats);
	$stats=str_replace("|","",$stats);
	$tmp=explode("\n",$stats);
	unset($stats);
	for($x=0;$x<sizeof($tmp);$x++) {
		$ep=strpos($tmp[$x],"=");
        $key=trim(substr($tmp[$x],0,$ep));
        if($key!='image') {
		    $val=substr($tmp[$x],$ep+1);
            $stats[trim($key)]=trim($val);
        }
	}	
	preg_match('/{{crafting recipe[^}]+}}/',$rev,$ingredients);		
	$ingredients=$ingredients[0];
	$ingredients=str_replace("{{crafting recipe", "", $ingredients);
	$ingredients=str_replace("}}", "", $ingredients);
	$ingredients=str_replace("|", "", $ingredients);
	$tmp=explode("\n",$ingredients);
	unset($ingredients);
	for($x=0;$x<sizeof($tmp);$x++) {
		$ep=strpos($tmp[$x],"=");
		$key=trim(substr($tmp[$x],0,$ep));
		$val=trim(substr($tmp[$x],$ep+1));
		$num=substr($key,-1);
		if(is_numeric($num)) {
			$num--;
			$key=substr($key,0,strlen($key)-1);
			$ingredients[$num][$key]=$val;
		}
	}
	//var_dump($stats);
	if(sizeof($stats) > 1) {
		$db->query("DELETE FROM item_stats WHERE itemid=$item[id]");
	}
	foreach($stats as $key=>$val) {
		if(!empty($val)) {			
			switch($key) {
				default: {
					$key{0}=strtoupper($key{0});
				}
				break;
				case "stack": {
					$key="Max Stack";
				}
				break;
				case "sspeed": {
					$key="Use Time";
				}
				break;
			}
			$db->query("INSERT INTO item_stats (itemid, name, value) VALUES ($item[id],'$key','$val')");
		}
	}
	//var_dump($ingredients);
	if(sizeof($ingredients)>0) {
		$db->query("DELETE FROM item_ingredients WHERE itemid=$item[id]");
	}
	for($x=0;$x<sizeof($ingredients);$x++) {
		$i=$ingredients[$x];
		$db->query("INSERT INTO item_ingredients (itemid, name, amt) VALUES ($item[id],'$i[item]','$i[amount]')");
	}
	print "Data for [ITEM] $item[name] created\n";
}

$db->query("UPDATE item_ingredients SET amt=1 WHERE amt=0");
print "Fixed null ingredient amounts\n";
//$db->query("DELETE FROM item_stats WHERE `name`='Image'");
//print "Removed Image values from item_stats\n";
//MONSTERS!
if(!opendir('Monsters/')) {
	mkdir('Monsters');
}
exec("rm Monsters/*");
$sql=$db->query("SELECT name, id FROM monsters");
while($monster=$sql->fetch_array()) {
    $name=str_replace(" ","_",$monster['name']);
    $url=str_replace("{OBJECT}",$name,$urlTemplate);
    curl_setopt($cp, CURLOPT_URL, $url);
    $data=curl_exec($cp);
    file_put_contents("Monsters/$monster[name].txt",$data);
    $xml=new SimpleXMLElement($data);
    $rev=$xml->query->pages->page->revisions->rev;
    preg_match('/{{npc infobox[^}]+}}/',$rev,$stats);
    $stats=$stats[0];
    $stats=str_replace("{{npc infobox","",$stats);
    $stats=str_replace("}}", "", $stats);
    $stats=str_replace("|","",$stats);
    $tmp=explode("\n",$stats);
    unset($stats);
    for($x=0;$x<sizeof($tmp);$x++) {
        $ep=strpos($tmp[$x],"=");
        $key=trim(substr($tmp[$x],0,$ep));
        if($key!='image') {
            $val=substr($tmp[$x],$ep+1);
            $stats[$key]=trim($val);
        }
    }
    if(sizeof($stats) >1) {
        $db->query("DELETE FROM monster_stats WHERE mid=$monster[id]");
    }
    foreach($stats as $key=>$val) {
        if(!empty($val)) {
            $key{0}=strtoupper($key{0});
            $db->query("INSERT INTO monster_stats (mid, name, value) VALUES ($monster[id], '$key', '$val')");
        }
    }
    print "Data for [MONSTER] $monster[name] created\n";
}
?>
