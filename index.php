<?php
date_default_timezone_set("America/Sao_Paulo");

include 'scraper.lib.php';
include('GoogleVoice.php');

// NOTE: Full email address required, password and country code in Caps
$gv = new GoogleVoice('usermail@gmail.com', 'password', 'US');

$html  = spcurl('https://www.google.com/voice/inbox/recent/missed');
$html = preg_replace("/\s+/"," ",$html);
file_put_contents('sample.xml', $html);
$html = str_get_html($html);

preg_match('/\[CDATA\[(.+)\]\]/', $html , $matches);
$data =  json_decode(preg_replace('/\]><\/json.*/', "",str_replace("[CDATA", "", $matches[0])));
foreach ($data[0]->messages as $key => $value) {
	print_r(array($value,$value->phoneNumber, $value->isRead));
	
}

