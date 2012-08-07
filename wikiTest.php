<?php
if($argc>1) {
    $url="http://wiki.terrariaonline.com/$argv[1]?action=render";    
    $cp=curl_init();
    curl_setopt($cp, CURLOPT_HEADER, 0);
    curl_setopt($cp, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($cp, CURLOPT_URL, $url);
    $data=curl_exec($cp);
    file_put_contents('wikiTemp.html', $data);
}
else {
    print "No wiki page given";
}
?>
