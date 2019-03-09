<?php
$test = '{
	"device": {
		"device": "nano",
		"serial": "8250a6",
		"hw_version": "v1",
		"sw_version": "1.0.4"
	},
	"update": {
		"available": "true",
		"version": "v0.9.12",
		"firmware": {
			"url": "http://update.wlanthermo.de/getFirmware.php?device=nano&serial=8250a6&version=v0.9.12"
		},
		"spiffs": {
			"url": "http://update.wlanthermo.de/getSpiffs.php?device=nano&serial=8250a6&version=v0.9.12"
		}
	},
	"runtime": 0.12083196640015
}';
$JsonArr = json_decode( $test, true );

	$json = json_encode($JsonArr, JSON_UNESCAPED_SLASHES);	
	//SimpleLogger::debug("".$json."\n");
	//SimpleLogger::debug("".strlen($json)."\n");
	header('Access-Control-Allow-Origin: *'); 
	header('Content-type: application/json');
	header("Content-Length: ".strlen($json));
	echo $json;
?>