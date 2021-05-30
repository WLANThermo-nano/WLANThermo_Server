<?php
 /*************************************************** 
    Copyright (C) 2021  Florian Riedl
    ***************************
		@author Florian Riedl
		@version 1.2.2, 12/05/21
	***************************
	This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
    
    HISTORY: Please refer Github History
    
 ****************************************************/

// error reporting
error_reporting(E_ALL); 

// start runtome counter
$time_start = microtime(true);

// include logging libary 
require_once("../include/SimpleLogger.php"); // logger class
SimpleLogger::$debug = false;

// include database and logfile config
if(stristr($_SERVER['SERVER_NAME'], 'dev-')){
	require_once("../include/dev-db.class.php");
	require_once("../dev-config.inc.php"); // REMOVE
	SimpleLogger::$filePath = '../logs/dev-api.wlanthermo.de/api_'.strftime("%Y-%m-%d").'.log';
	SimpleLogger::info("load ../dev-db.class.php\n");
}else{
	require_once("../include/db.class.php");
	require_once("../config.inc.php"); // REMOVE
	SimpleLogger::$filePath = '../logs/api.wlanthermo.de/api_'.strftime("%Y-%m-%d").'.log';
	SimpleLogger::info("load ../db.class.php\n");
}	

// include device libary
require_once("../include/device.class.php");

// include cloud libary
require_once("../include/cloud.class.php");

// include notification libary
require_once("../include/notification.class.php");

// log IP-Adress
SimpleLogger::info("IP-Adress:".$_SERVER['REMOTE_ADDR']."\n");
 
// read input data
$json = file_get_contents('php://input');
SimpleLogger::info("file_get_contents input:".$json."\n");

// decode & check input data
$JsonArr = json_decode( $json, true );

if (($JsonArr === null && json_last_error() !== JSON_ERROR_NONE) OR checkDeviceJson($JsonArr) === false) {
    http_response_code(400); // Bad request
	SimpleLogger::error("JSON invalide\n");
	SimpleLogger::dump($json . "\n");
	exit;
}

 /*************************************************** 
	main 
 ****************************************************/

$device = new Device($JsonArr['device']['device'], $JsonArr['device']['serial'], $JsonArr['device']['hw_version'], $JsonArr['device']['sw_version'], getCpuVersion($JsonArr), getFlashSize($JsonArr), getItem($JsonArr));

/* check device status */ 
if(!$device->getDeviceActive()){
	http_response_code(401); // Unauthorized
	echo 'Device is not authorized...';
	exit;		
}
	
foreach($JsonArr as $key => $value){
	switch ($key) {
		
		case 'update':	// process update 
			if(isset($JsonArr['update']['version']) AND !empty($JsonArr['update']['version'])){
				$JsonArr = (isset($JsonArr['update']['file']) AND !empty($JsonArr['update']['file'])) ? createUpdateJson($JsonArr,$device->getSoftwareByFileType($JsonArr['update']['version'], $JsonArr['update']['file'])) : createUpdateJson($JsonArr,$device->getSoftwareByVersion($JsonArr['update']['version']));
			}else{
				$JsonArr = createUpdateJson($JsonArr,$device->getSoftwareUpdate(getPreReleaseFlag($JsonArr)));
			}
			break;
			
		case 'cloud':	// process cloud
			$cloud = new Cloud();				
			switch ($JsonArr['cloud']['task']) {
				
				case 'save':	// process cloud save						
					$JsonArr['cloud']['task'] = $cloud->insertCloudData($JsonArr['device']['serial'],$JsonArr['cloud']['api_token'],$JsonArr['cloud']['data']) ? 'true' : 'false';
					unset($JsonArr['cloud']['data']);
					break;
				
				case 'read':	// process cloud read	
					$from = isset($JsonArr['cloud']['from']) ? $JsonArr['cloud']['from'] : null;
					$to = isset($JsonArr['cloud']['to']) ? $JsonArr['cloud']['to'] : null;
					$data = $cloud->readCloudData($JsonArr['cloud']['api_token'], $from, $to);
					if($data){
						$JsonArr['cloud']['task'] = true;
						$JsonArr['cloud']['data'] = $data;
					}else{
						$JsonArr['cloud']['task'] = false;
					}
					break;	
				
				default:
				   $JsonArr['cloud']['task'] = 'false';						
			}			
			break;
			
		case 'history':	// process history
			// $JsonArr = createHistoryJson($dbh,$JsonArr);
			break;
		
		case 'notification':	// process notification
			$notification = new Notification();
			switch ($JsonArr['notification']['task']){
				case 'alert':			
					foreach($JsonArr['notification']['services'] as $key => $value){
						switch ($value['service']) {
							case 'telegram':
								$notification->sendTelegram($value['key1'],$value['key2'],$notification->getMessage($JsonArr['notification']['message'],$JsonArr['notification']['lang'] ?? "en",$JsonArr['notification']['channel'] ?? "",$JsonArr['notification']['temp'][0] ?? "",$JsonArr['notification']['temp'][1] ?? ""),$JsonArr['notification']['sound'] ?? "");	
								break;
					
							case 'pushover':
								$notification->sendPushover($value['key1'],$value['key2'],$notification->getMessage($JsonArr['notification']['message'],$JsonArr['notification']['lang'] ?? "en",$JsonArr['notification']['channel'] ?? "",$JsonArr['notification']['temp'][0] ?? "",$JsonArr['notification']['temp'][1] ?? ""));
								break;
						}
					}
					break;
			}
			break;

		case 'notification_v2':	// process notification
			$notification = new Notification();

			foreach($JsonArr['notification_v2']['services'] as $key => $value){
				switch ($value['service']) {
					case 'telegram':
						$notification->sendTelegram($value['token'],$value['chat_id'],$notification->getMessage($JsonArr['notification_v2']['message']['type'],$JsonArr['device']['language'] ?? "en",$JsonArr['notification_v2']['message']['channel'] + 1 ?? "",$JsonArr['notification_v2']['message']['temp'] ?? "",$JsonArr['notification_v2']['message']['limit'] ?? ""));	
						break;
			
					case 'pushover':
						$notification->sendPushover($value['token'],$value['user_key'],$notification->getMessage($JsonArr['notification_v2']['message']['type'],$JsonArr['device']['language'] ?? "en",$JsonArr['notification_v2']['message']['channel'] + 1 ?? "",$JsonArr['notification_v2']['message']['temp'] ?? "",$JsonArr['notification_v2']['message']['limit'] ?? ""),$value['priority'] ?? "0",$value['retry'] ?? "30",$value['expire'] ?? "300");
						break;
								
					case 'app':
						$notification->sendFirebaseNotification($firebase_server_key,$value['token'],$notification->getMessage($JsonArr['notification_v2']['message']['type'],$JsonArr['device']['language'] ?? "en",$JsonArr['notification_v2']['message']['channel'] + 1 ?? "",$JsonArr['notification_v2']['message']['temp'] ?? "",$JsonArr['notification_v2']['message']['limit'] ?? ""),$value['sound'] ?? "default");
						break;
				}
			}
			break;
			
		case 'alexa':	// process alexa
			// $JsonArr = createAlexaJson($dbh,$JsonArr);
			break;
	}
}

$JsonArr['runtime'] = (microtime(true) - $time_start);
$json = json_encode($JsonArr, JSON_UNESCAPED_SLASHES);	
//SimpleLogger::info("file_get_contents output:".$json."\n");
//SimpleLogger::info("".$json."\n");

header('Access-Control-Allow-Origin: *'); 
header('Content-Type: application/json');
header("Content-Length: ".strlen($json));
SimpleLogger::info("Runtime 1:".(microtime(true) - $time_start)."\n");
echo $json;	
SimpleLogger::info("Runtime 2:".(microtime(true) - $time_start)."\n");
 /*************************************************** 
	WLANThermo API functions 
 ****************************************************/

function checkDeviceJson($JsonArr){
	if (isset($JsonArr['device']['device']) AND !empty($JsonArr['device']['device']) AND 
		isset($JsonArr['device']['serial']) AND !empty($JsonArr['device']['serial']) AND 
		isset($JsonArr['device']['hw_version']) AND !empty($JsonArr['device']['hw_version']) AND 
		isset($JsonArr['device']['sw_version']) AND !empty($JsonArr['device']['sw_version']))
	{
		return true;
	}else{
		return false;
	}
}

//-----------------------------------------------------------------------------

function getPreReleaseFlag($JsonArr){
	if(isset($JsonArr['update']['prerelease']) AND !empty($JsonArr['update']['prerelease']) AND is_bool($JsonArr['update']['prerelease'])){
		return $JsonArr['update']['prerelease'];
	}else{
		return false;
	}
}

function getCpuVersion($JsonArr){
	if(isset($JsonArr['device']['cpu']) AND !empty($JsonArr['device']['cpu'])){
		return $JsonArr['device']['cpu'];
	}else{
		return 'esp82xx'; //default
	}
}

function getItem($JsonArr){
	if(isset($JsonArr['device']['item']) AND !empty($JsonArr['device']['item'])){
		return $JsonArr['device']['item'];
	}else{
		return null; //default
	}
}

function getFlashSize($JsonArr){
	if(isset($JsonArr['device']['flash_size']) AND !empty($JsonArr['device']['flash_size'])){
		return $JsonArr['device']['flash_size'];
	}else{
		return '0'; //default
	}
}

/**
 * create update json
 *
 * @param array,array
 * @return array
**/	
function createUpdateJson($JsonArr,$softwareArr){
	if($softwareArr){
		$JsonArr['update']['available'] = 'true';
		$JsonArr['update']['version'] = $softwareArr[0]['software_version'];
		$keys = array_keys($softwareArr);
		$JsonArr['update']['available'] = 'true';
		$JsonArr['update']['version'] = $softwareArr[0]['software_version'];
		$keys = array_keys($softwareArr);
		for($i = 0; $i < count($softwareArr); $i++) {
			$JsonArr['update'][$softwareArr[$keys[$i]]['file_type']]['url'] = "http://update.wlanthermo.de/getFile.php?asset_id=".$softwareArr[$keys[$i]]['asset_id']."";  //$softwareArr[$keys[$i]]['file_url'];
			// $JsonArr['update'][$softwareArr[$keys[$i]]['file_type']]['sha256'] = $softwareArr[$keys[$i]]['file_sha256'];
			$JsonArr['update'][$softwareArr[$keys[$i]]['file_type']]['asset_id'] = $softwareArr[$keys[$i]]['asset_id'];			
		}					
	}else{
		$JsonArr['update']['available'] = 'false';
	}
	
	return $JsonArr;
}

//-----------------------------------------------------------------------------

function isAssoc($arr){
	if (count($arr) == count($arr, COUNT_RECURSIVE)){
		return false;
	}else{
		return true;
	}
}

//-----------------------------------------------------------------------------

function guidv4(){
    $data = openssl_random_pseudo_bytes( 16 );
    $data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 ); // set version to 0100
    $data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 ); // set bits 6-7 to 10
    return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
}