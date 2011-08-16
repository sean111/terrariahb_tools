<?php
require('phpQuery.php');
$url='http://wiki.terrariaonline.com/Clay_Pot?action=render';
$cp=curl_init();
curl_setopt($cp, CURLOPT_HEADER, 0);
curl_setopt($cp, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($cp, CURLOPT_URL, $url);
$data=curl_exec($cp);
$doc=phpQuery::newDocument($data);
pq('.editsection')->remove();
pq('.mw-redirect')->removeAttr('href');
pq('p a')->removeAttr('href');

foreach(pq('.craftbox a') as $link) {
    $title=pq($link)->attr('title');
    pq($link)->attr('href','#'.$title);
}
foreach(pq('img') as $img) {
    $alt=pq($img)->attr('alt');
    pq($img)->attr('src','img/'.$alt);
}
print $doc;
