<?php
 /*************************************************** 
    Copyright (C) 2020  Florian Riedl
    ***************************
		@author Florian Riedl
		@version 1.0, 25/04/20
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
SimpleLogger::$debug = true;

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

	//Remove
try {
	$dbh = new PDO(sprintf('mysql:host=%s;dbname=%s', $db_server, $db_name), $db_user, $db_pass);
	$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
	$dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
} catch (PDOException $e) {
	//SimpleLogger::error("Database - An error has occurred\n");
	//SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
	die('false');
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
					break;
				case 'read':	// process cloud read
					// todo
					break;	
				default:
				   $JsonArr['cloud']['task'] = 'false';						
			}
			unset($JsonArr['cloud']['data']);
			break;
		case 'history':	// process history
			// $JsonArr = createHistoryJson($dbh,$JsonArr);
			break;
		case 'notification':	// process notification
			$JsonArr = createNotificationJson($JsonArr);
			break;
		case 'alexa':	// process alexa
			// $JsonArr = createAlexaJson($dbh,$JsonArr);
			break;
	}
}

$JsonArr['runtime'] = (microtime(true) - $time_start);
$json = json_encode($JsonArr, JSON_UNESCAPED_SLASHES);	
SimpleLogger::info("file_get_contents output:".$json."\n");
//SimpleLogger::info("".$json."\n");

header('Access-Control-Allow-Origin: *'); 
header('Content-Type: application/json');
header("Content-Length: ".strlen($json));
echo $json;	

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

function createHistoryJson($dbh,$JsonArr){
	if (isset($JsonArr['history']['task']) AND !empty($JsonArr['history']['task'])){
		switch ($JsonArr['history']['task']) {
			case 'save':
				if (isset($JsonArr['history']['api_token']) AND !empty($JsonArr['history']['api_token'])){			
					if (insertHistoryData($dbh,$JsonArr)){
						$JsonArr['history']['task'] = 'true';
					}else{
						$JsonArr['history']['task'] = 'false';
					}
				}else{
					$JsonArr['history']['task'] = 'false';	
					SimpleLogger::debug("Json false - ".json_encode($JsonArr['history'], JSON_UNESCAPED_SLASHES)."(createHistoryJson)\n");
				}
				break;
			case 'read':
				$tmp = readHistoryData($dbh,$JsonArr);
				if ($tmp == false){
						$JsonArr['history']['task'] = 'false';
					}else{
						$JsonArr['history']['task'] = 'true';
						$JsonArr['history']['list'] = $tmp;
				}
				break;	
			case 'delete':
				$tmp = deleteHistoryData($dbh,$JsonArr);
				if ($tmp == false){
						$JsonArr['history']['task'] = 'false';
					}else{
						$JsonArr['history']['task'] = 'true';
				}
				break;				
		}
	}else{
		$JsonArr['history']['task'] = 'false';	
		SimpleLogger::debug("Json false - ".json_encode($JsonArr['history'], JSON_UNESCAPED_SLASHES)."(createHistoryJson)\n");
	}
	return $JsonArr;
}

function readHistoryData($dbh,$JsonArr){	
	try {
		$tmp = array();
		$sql = "SELECT api_token, ts_start, ts_stop FROM `history` WHERE serial= :serial order by `id` desc";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':serial', $JsonArr['device']['serial']);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		if ($statement->rowCount() > 0) {
			foreach($statement as $key => $daten) {
				array_push($tmp, $daten);
			}
			return $tmp;
		}else{
			return false;	
		}
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (readHistoryData)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		return false;
	}
}

function deleteHistoryData($dbh,$JsonArr){	
	try {
		$tmp = array();
		$sql = "DELETE FROM `history` WHERE api_token= :api_token";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':api_token', $JsonArr['history']['api_token']);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		if ($statement->rowCount() > 0) {
			return true;
		}else{
			return false;	
		}
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (readHistoryData)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		return false;
	}
}
		
function insertHistoryData($dbh,$JsonArr){	
	try {
		$api_time = '24';
		$sql = "SELECT data FROM `cloud` WHERE api_token= :api_token AND serial= :serial AND `time` > TIMESTAMP(DATE_SUB(NOW(), INTERVAL :history_time hour)) order by `id` asc";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':api_token', $JsonArr['history']['api_token']);
		$statement->bindValue(':serial', $JsonArr['device']['serial']);
		$statement->bindValue(':history_time', $api_time);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		$tmp = array();
		$data = array();
		SimpleLogger::debug($c);
		if ($statement->rowCount() > 0) {
			$numItems = $statement->rowCount() - 1;
			foreach($statement as $key => $daten) {
				$obj = json_decode( $daten['data'], true );
				if($key == $numItems){
					$data['header']['ts_stop'] = $obj['system']['time'];
					$arr = $obj;
					if(isset($obj['pitmaster'])){	
						if(!isAssoc($obj['pitmaster'])){
							unset($arr['pitmaster']);
							$arr['pitmaster'][0] = $obj['pitmaster'];
						}
					}					
					$data['last_data'] = $arr;
					
				} else {
					if($key == 0){
						$data['header']['ts_start'] = $obj['system']['time'];
					}
					if ($obj === null && json_last_error() !== JSON_ERROR_NONE) {
					}else{
						$arr = array(); 
						$arr['system']['time'] = $obj['system']['time'];
						$arr['system']['soc'] = $obj['system']['soc'];
						foreach ( $obj['channel'] as $key => $value )
						{
							$arr['channel'][$key]['temp'] = $value['temp'];
						}
						if(isAssoc($obj['pitmaster'])){
							foreach ($obj['pitmaster'] as $key => $value)
							{	
								$arr['pitmaster'][$key]['value'] = $value['value'];
								$arr['pitmaster'][$key]['set'] = $value['set'];
								$arr['pitmaster'][$key]['typ'] = $value['typ'];
							}					
						}else{
							$arr['pitmaster'][0]['value'] = $obj['pitmaster']['value'];
							$arr['pitmaster'][0]['set'] = $obj['pitmaster']['set'];
							$arr['pitmaster'][0]['typ'] = $obj['pitmaster']['typ'];						
						}
						array_push($tmp, $arr);
					}						// not last element
				}
			}		
			$data['data'] = $tmp;
			//array_unshift($data, $data['settings']);
			$sql = "INSERT INTO `history` (`api_token`,`serial`,`ts_start`,`ts_stop`,`data`) VALUES (:api_token, :serial, :ts_start, :ts_stop, :data)";
			$statement = $dbh->prepare($sql);			
			$statement->bindValue(':data', json_encode($data, JSON_UNESCAPED_SLASHES));
			$statement->bindValue(':serial', $JsonArr['device']['serial']);
			$statement->bindValue(':api_token', guidv4());
			$statement->bindValue(':ts_start', $data['header']['ts_start']);
			$statement->bindValue(':ts_stop', $data['header']['ts_stop']);
			$statement->execute();
			return true;	
				//return(json_encode($data));
		} else {
			return false;
		}	
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (insertHistoryData)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		return false;
	}
}

//-----------------------------------------------------------------------------

function checkAlexaJson($JsonArr){
	if (isset($JsonArr['alexa']['task']) AND !empty($JsonArr['alexa']['task'])){
		return true;
	}else{
		return false;
	}
}

function createAlexaJson($dbh,$JsonArr){
	if(checkAlexaJson($JsonArr)){
		switch ($JsonArr['alexa']['task']) {
			case 'save':
				if (insertAlexaKey($dbh,$JsonArr)){
					$JsonArr['alexa']['task'] = 'true';
				}else{
					$JsonArr['alexa']['task'] = 'false';
				}
				break;
			case 'delete':
				$JsonArr['alexa']['token'] = NULL;
				if (insertAlexaKey($dbh,$JsonArr)){
					$JsonArr['alexa']['task'] = 'true';
				}else{
					$JsonArr['alexa']['task'] = 'false';
				}
				unset($JsonArr['alexa']['token']);
				break;				
		}
	}else{
		$JsonArr['alexa']['task'] = 'false';	
		SimpleLogger::debug("Json false - ".json_encode($JsonArr['alexa'], JSON_UNESCAPED_SLASHES)."(createAlexaJson)\n");
	}
	return $JsonArr;	
}

function insertAlexaKey($dbh,$JsonArr){
	try {			
		$sql = "UPDATE `devices` 
				SET `amazon_token` = :token 
				WHERE `serial` = :serial";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':serial', $JsonArr['device']['serial']);
		$statement->bindValue(':token', $JsonArr['alexa']['token']);
		$statement->execute();			
		return true;
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (insertAlexaKey)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		return false;
	}
}
//-----------------------------------------------------------------------------

function checkNotificationJson($dbh,$JsonArr){
	if (isset($JsonArr['notification']['task']) AND !empty($JsonArr['notification']['task'])){
		return true;
	}else{
		return false;
	}
}

function createNotificationJson($JsonArr){
	switch ($JsonArr['notification']['task']) {
		case 'alert':
			sendNotification($JsonArr);
			break;
	}
	return $JsonArr;
}

function sendNotification($JsonArr){
	foreach($JsonArr['notification']['services'] as $key => $value){
		switch ($value['service']) {
			case 'telegram':	
				sendTelegram($JsonArr,$value);
				break;
			case 'telegram-bot':
				sendTelegramBot($JsonArr,$value);
				break;
			case 'pushover':
				sendPushover($JsonArr,$value);
				break;
			case 'mail':
				// ToDo
				break;
		}
	}
}

function getMsg($JsonArr){

	$de_alert_up = 'ACHTUNG! Kanal %s: Temperatur (%s°%s) ist zu hoch (%s°%s)';
	$de_alert_down = 'ACHTUNG! Kanal %s: Temperatur (%s°%s) ist zu tief (%s°%s)';
	$en_alert_up = 'ATTENTION! Channel %s: Temperature (%s°%s) is too high (%s°%s)';
	$en_alert_down = 'ATTENTION!  Channel %s: Temperature (%s°%s) is too low (%s°%s)';
	
	$de_alert_battery = 'Achtung: Die Batterieladung ist niedrig! Bitte ein Netzteil anschließen.';
	$en_alert_battery = 'Attention: Battery charge is low! Please connect a power adapter.';
	$de_alert_test = 'Testnachricht erfolgreich gesendet. Deine Einstellungen sind korrekt.';
	$en_alert_test = 'Message sent successfully. Your settings are correct.';
	
	
	switch ($JsonArr['notification']['lang']) {
		case 'de':
			if($JsonArr['notification']['message'] == 'up'){
			return sprintf($de_alert_up, $JsonArr['notification']['channel'],$JsonArr['notification']['temp'][0],$JsonArr['notification']['unit'],$JsonArr['notification']['temp'][1],$JsonArr['notification']['unit']);
			}else if($JsonArr['notification']['message'] === 'down'){
				return sprintf($de_alert_down, $JsonArr['notification']['channel'],$JsonArr['notification']['temp'][0],$JsonArr['notification']['unit'],$JsonArr['notification']['temp'][1],$JsonArr['notification']['unit']);
			}else if($JsonArr['notification']['message'] === 'battery'){
				return $de_alert_battery;	
			}else if($JsonArr['notification']['message'] === 'test'){
				return $de_alert_test;
			}
			break;
		case 'en':
			if($JsonArr['notification']['message'] == 'up'){
				return sprintf($en_alert_up , $JsonArr['notification']['channel'] , $JsonArr['notification']['temp'][0] , $JsonArr['notification']['unit'] , $JsonArr['notification']['temp'][1] , $JsonArr['notification']['unit']);
			}else if($JsonArr['notification']['message'] === 'down'){
				return sprintf($en_alert_down , $JsonArr['notification']['channel'] , $JsonArr['notification']['temp'][0] , $JsonArr['notification']['unit'] , $JsonArr['notification']['temp'][1] , $JsonArr['notification']['unit']);
			}else if($JsonArr['notification']['message'] === 'battery'){
				return $en_alert_battery;	
			}else if($JsonArr['notification']['message'] === 'test'){
				return $en_alert_test;
			}
		default:
			if($JsonArr['notification']['message'] == 'up'){
				return sprintf($en_alert_up, $JsonArr['notification']['channel'],$JsonArr['notification']['temp'][0],$JsonArr['notification']['unit'],$JsonArr['notification']['temp'][1],$JsonArr['notification']['unit']);
			}else if($JsonArr['notification']['message'] === 'down'){
				return sprintf($en_alert_down, $JsonArr['notification']['channel'],$JsonArr['notification']['temp'][0],$JsonArr['notification']['unit'],$JsonArr['notification']['temp'][1],$JsonArr['notification']['unit']);
			}else if($JsonArr['notification']['message'] === 'battery'){
				return $en_alert_battery;	
			}else if($JsonArr['notification']['message'] === 'test'){
				return $en_alert_test;
			}
	}
}

function sendTelegram($JsonArr,$services){	
	$url = 'https://api.telegram.org/bot' . $services['key1'] . '/sendMessage?text="' . getMsg($JsonArr) . '"&chat_id=' . $services['key2'];
	$result = json_decode(file_get_contents($url));
	if($result->ok === true){
		SimpleLogger::info("Message has been sent! \n");
	}else{
		SimpleLogger::error("Message could not be sent! \n");		
	}
}

function sendTelegramBot($JsonArr,$services){	
	global $telegram_bot_api;
	$url = 'https://api.telegram.org/bot' . $telegram_bot_api . '/sendMessage?text="' . getMsg($JsonArr) . '"&chat_id=' . $services['key2'];
	$result = json_decode(file_get_contents($url));
	if($result->ok === true){
		SimpleLogger::info("Message has been sent! \n");
	}else{
		SimpleLogger::error("Message could not be sent! \n");		
	}
}

function sendPushover($JsonArr,$services){
	curl_setopt_array($ch = curl_init(), array(
	  CURLOPT_URL => "https://api.pushover.net/1/messages.json",
	  CURLOPT_POSTFIELDS => array(
		"token" => $services['key1'],
		"user" => $services['key2'],
		"message" => getMsg($JsonArr),
	  ),
	  CURLOPT_SAFE_UPLOAD => true,
	  CURLOPT_RETURNTRANSFER => true,
	));
	curl_exec($ch);
	curl_close($ch);
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