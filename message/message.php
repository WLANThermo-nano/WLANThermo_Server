<?php
error_reporting(E_ALL);

if (isset($_GET['token'])){
$url = 'https://api.telegram.org/bot' . $_GET['token'] . '/getUpdates?offset=0';	
$ch = curl_init();
// Disable SSL verification
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// Will return the response, if false it print the response
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Set the url
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: ESP8285'));
// Execute
$result = curl_exec($ch);
echo $result;
}else{
	echo 'false';
	
}
