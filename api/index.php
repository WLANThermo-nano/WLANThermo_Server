<?php
error_reporting(E_ALL);

/* @author Florian Riedl
 * @version 0.2, 26/02/18 
 */	
 
//-----------------------------------------------------------------------------
// include Logging libary 
$logfile = '_api.log'; // global var for logger class filename
$logpath = '../logs/';  // global var for logger class filepath
require_once("../include/logger.php"); // logger class
//-----------------------------------------------------------------------------
// include database config
require_once("../../config.inc.php"); // 
//-----------------------------------------------------------------------------
// read $_POST data
$test = '{
	"device": {
		"device": "nano",
		"serial": "84d1ac",
		"hw_version": "1",
		"sw_version": "v0.9.7"
	},
	"update": {
		"available": true
	},
	"cloud": {
		"task": "save",
		"api_token": "blablabla",
		"data":[] 
	},
	"url":{
		"api": {
			"host": "api.wlanthermo.de",
			"page": "/index.php"
		},
		"firmware": {
			"host": "update.wlanthermo.de",
			"page": "/getFirmware.php"
		},
		"spiffs": {
			"host": "update.wlanthermo.de",
			"page": "/getSpiffs.php"
		},
		"cloud": {
			"host": "cloud.wlanthermo.de",
			"page": "/saveData.php"
		},
		"notification": {
			"host": "message.wlanthermo.de",
			"page": "/message.php"
		},
		"thingspeak": {
			"host": "api.thingspeak.com",
			"page": "/update.json"
		}
	}
}';


$json = file_get_contents('php://input');
$JsonArr = array();
//$JsonArr = json_decode( $test, true );
$JsonArr = json_decode( $json, true );
if ($JsonArr === null && json_last_error() !== JSON_ERROR_NONE) {
    SimpleLogger::error("JSON invalide\n");
	SimpleLogger::debug("".$json."\n");
	die(false);
}

//-----------------------------------------------------------------------------
// main 

if(checkDeviceJson($JsonArr)){
	// Connecting to database
	try {
		$dbh = new PDO(sprintf('mysql:host=%s;dbname=%s', $db_server, $db_name), $db_user, $db_pass);
		$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
		$dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
	} catch (PDOException $e) {
		SimpleLogger::error("Database - An error has occurred\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die('false');
	}
	
	foreach($JsonArr as $key => $value){
		switch ($key) {
			case 'update':	
				$JsonArr = createUpdateJson($dbh,$JsonArr);
				break;
			case 'cloud':
				$JsonArr = createCloudJson($dbh,$JsonArr);
				break;
		}
	}
	$json = json_encode($JsonArr, JSON_UNESCAPED_SLASHES);	
	header('Content-Type: application/json');
	header("Content-Length: ".strlen($json));
	echo $json;	
}else{
	SimpleLogger::error("(checkDeviceJson) JSON device bad\n");
	SimpleLogger::debug("".$json."\n");
	die(false);
}


// function
function checkDeviceJson($JsonArr){
	if (isset($JsonArr['device']['device']) AND !empty($JsonArr['device']['device']) AND isset($JsonArr['device']['serial']) AND !empty($JsonArr['device']['serial']) AND isset($JsonArr['device']['hw_version']) AND !empty($JsonArr['device']['hw_version']) AND isset($JsonArr['device']['sw_version']) AND !empty($JsonArr['device']['sw_version'])){
		return(true);
	}else{
		return(false);
	}
}

function createUpdateJson($dbh,$JsonArr){
	if(checkDeviceDatabase($dbh,$JsonArr)){
		$newVersion = checkNewUpdate($dbh,$JsonArr);
		if ($newVersion != 'false'){
			$JsonArr['update']['available'] = 'true';
			$JsonArr['update']['version'] = $newVersion;
			$JsonArr['update']['firmware']['url'] = 'http://update.wlanthermo.de/getFirmware.php?device='.$JsonArr['device']['device'].'&serial='.$JsonArr['device']['serial'].'&version='.$JsonArr['update']['version'].'';
			$JsonArr['update']['spiffs']['url'] = 'http://update.wlanthermo.de/getSpiffs.php?device='.$JsonArr['device']['device'].'&serial='.$JsonArr['device']['serial'].'&version='.$JsonArr['update']['version'].'';
			return $JsonArr;
		}else{
			$JsonArr['update']['available'] = 'false';
			return $JsonArr;
		}
	}else{
		SimpleLogger::error("An error has occurred - (createUpdateJson)\n");
		die(false);		
	}	
}

function createCloudJson($dbh,$JsonArr){
	if(checkCloudJson){
		switch ($JsonArr['cloud']['task']) {
			case 'save':
				if (isset($JsonArr['cloud']['data']) AND !empty($JsonArr['cloud']['data'])){
					$JsonArr = insertCloudData($dbh,$JsonArr);
				}else{
					$JsonArr['cloud']['task'] = 'false';
				}
				break;
			case 'read':
				// todo
				break;		
		}
	}else{
		$JsonArr['cloud']['task'] = 'false';	
		SimpleLogger::debug("Json false - ".json_encode($JsonArr['cloud'], JSON_UNESCAPED_SLASHES)."(createUpdateJson)\n");
	}
	return $JsonArr;
}

function checkCloudJson($dbh,$JsonArr){
	if (isset($JsonArr['cloud']['task']) AND !empty($JsonArr['cloud']['task']) AND isset($JsonArr['cloud']['api_token']) AND !empty($JsonArr['cloud']['api_token'])){
		return(true);
	}else{
		return(false);
	}
}

function insertCloudData($dbh,$JsonArr){	
	try {
		$sql = "INSERT INTO `cloud` (`serial`, `api_token`, `data`) VALUES (:serial, :api_token, :data)";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':serial', $JsonArr['device']['serial']);
		$statement->bindValue(':api_token', $JsonArr['cloud']['api_token']);
		foreach($JsonArr['cloud']['data'] as $key => $data){			
			$statement->bindValue(':data', json_encode($data, JSON_UNESCAPED_SLASHES));
			$statement->execute();
		}		
		$statement->close();
		$JsonArr['cloud']['task'] = "true";
		unset($JsonArr['cloud']['data']);
		return $JsonArr;
	} catch (PDOException $e) {
		$statement->close();
		SimpleLogger::error("An error has occurred - (insertDevice)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		$JsonArr['cloud']['task'] = "false";
		unset($JsonArr['cloud']['data']);
		return $JsonArr;
	}		
}

function checkDeviceDatabase($dbh,$JsonArr){
	try {
		$sql = "INSERT INTO `devices` (`device`,`serial`, `name`, `hardware_version`, `software_version`, `update_active`, `whitelist`) 
				VALUES (:device, :serial, :name, :hardware_version, :software_version, :update_active, :whitelist) 
				ON DUPLICATE KEY UPDATE device=VALUES(device), hardware_version=VALUES(hardware_version), software_version=VALUES(software_version)";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':device', $JsonArr['device']['device']);
		$statement->bindValue(':serial', $JsonArr['device']['serial']);
		$statement->bindValue(':name', '');
		$statement->bindValue(':hardware_version', $JsonArr['device']['hw_version']);
		$statement->bindValue(':software_version', $JsonArr['device']['sw_version']);
		$statement->bindValue(':update_active', '1');
		$statement->bindValue(':whitelist', '0');
		$inserted = $statement->execute();
		if($inserted){
			return true;
		}else{
			return false;
		}
		$statement = null;
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (checkDeviceDatabase)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die('false');
	}
}

function checkNewUpdate($dbh,$JsonArr){
	try {
		$sql = "select s1.software_version from sw_versions as s1, 
				(SELECT d.serial, max(s.software_id) as software_id FROM `devices` as d, sw_versions as s WHERE 
				d.device = s.device and d.update_active = 1 and (d.whitelist = 1 or s.prerelease = 0) and d.serial = :serial
				group by d.serial) as s2
				where 
				s1.software_id = s2.software_id";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':serial', $JsonArr['device']['serial']);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		if ($statement->rowCount() > 0) {
		  $deviceInfo = $statement->fetch();
		  return compareVersion($deviceInfo['software_version'],$JsonArr['device']['sw_version']);
		} else {
		  return('false');
		}
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (checkNewUpdate)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		return('false');
	}	
}

function compareVersion($dbVersion, $deviceVersion){
	if (version_compare($dbVersion, $deviceVersion, ">")) {
		return $dbVersion;
	}else{
		return('false');
	}
}
?>