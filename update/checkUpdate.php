<?php
error_reporting(E_ALL);
$logfile = '_update.log'; // global var for logger class filename
$logpath = '../logs/';  // global var for logger class filepath
require_once("../include/logger.php");
require_once("../../config.inc.php");
SimpleLogger::info("############################################################\n");

/* @author Florian Riedl
 *
 * Example:
 *
 * Check for new update
 * 		http://nano.wlanthermo.de/checkUpdate.php?device="nano"&serial="Serialnummer"&hw_version="v1"&sw_version="currentVersion"
 * ----------------------------------------------------------------------------------------------------------------------------------------
 * Download Firmware-version XYZ 
 * 		http://nano.wlanthermo.de/checkUpdate.php?device="nano"serial="Serialnummer"&hw_version="v1"&sw_version="currentVersion"&getFirmware="XYZ"
 * ----------------------------------------------------------------------------------------------------------------------------------------
 * Download Spiffs-version XYZ 
 * 		http://nano.wlanthermo.de/checkUpdate.php?device="nano"serial="Serialnummer"&hw_version="v1"&sw_version="currentVersion"&getSpiffs="XYZ"
 * ---------------------------------------------------------------------------------------------------------------------------------------- 
 */	

if (isset($_GET['device']) AND !empty($_GET['device']) AND isset($_GET['serial']) AND !empty($_GET['serial']) AND isset($_GET['hw_version']) AND !empty($_GET['hw_version']) AND isset($_GET['sw_version']) AND !empty($_GET['sw_version'])){
	SimpleLogger::info("Device ".$_GET['device']."/".$_GET['serial']." is looking for an update...\n");
	//Connecting to database
	try {
		SimpleLogger::info("Connecting to the database...\n");
		$dbh = new PDO(sprintf('mysql:host=%s;dbname=%s', $db_server, $db_name), $db_user, $db_pass);
		$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
		$dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die('false');
	}
	
	$result = searchDevice($dbh,strval($_GET['serial'])); // Look for Device into Database		
	
	if($result !== false){
		SimpleLogger::info("Device '".$_GET['device']."/".$_GET['serial']."' found in database\n");
		if($result['device'] == strval($_GET['device']) AND $result['software_version'] == strval($_GET['sw_version']) AND $result['hardware_version'] == $_GET['hw_version']){
			SimpleLogger::info("Device '".$_GET['device']."/".$_GET['serial']."' in database is up to date\n");
		}else{
			SimpleLogger::info("Device '".$_GET['device']."/".$_GET['serial']."' in database is unequal\n");
			SimpleLogger::debug("Device:".$_GET['device']."/".$result['device']." Serial:".$_GET['serial']."/".$result['serial']." Hardware Version:".$_GET['hw_version']."/".$result['hardware_version']." Software Version:".$_GET['sw_version']."/".$result['software_version']."\n");
			$result = updateDevice($dbh,strval($_GET['device']),strval($_GET['serial']),$result['name'],strval($_GET['hw_version']),strval($_GET['sw_version']));
			if($result !== false){
				SimpleLogger::info("Device '".$_GET['device']."/".$_GET['serial']."' in database has been updated\n");
			}else{
				SimpleLogger::error("Device ".$_GET['device']."/".$_GET['serial']." in database has not been updated\n");
			}
		}	
	}else{
		SimpleLogger::info("Device ".$_GET['device']."/".$_GET['serial']." not found in database\n");
		$result = insertDevice($dbh,strval($_GET['device']),strval($_GET['serial']),'',strval($_GET['hw_version']),strval($_GET['sw_version']),'1','0');
		if($result !== false){
			SimpleLogger::info("New device '".$_GET['device']."/".$_GET['serial']."' stored in database\n");
		}else{
			SimpleLogger::error("New device ".$_GET['device']."/".$_GET['serial']." not stored in database\n");
		}
	}

	if (isset($_GET['getFirmware']) AND !empty($_GET['getFirmware'])){
		SimpleLogger::info("Device '".$_GET['device']."/".$_GET['serial']."' looking for Firmware ".$_GET['getFirmware']."\n");
		getFirmware($dbh,$_GET['getFirmware']);
		die();
	}elseif (isset($_GET['getSpiffs']) AND !empty($_GET['getSpiffs'])){
		SimpleLogger::info("Device '".$_GET['device']."/".$_GET['serial']."' looking for Spiffs ".$_GET['getSpiffs']."\n");
		getSpiffs($dbh,$_GET['getSpiffs']);
		die();
	}else{	
		checkUpdate($dbh,strval($_GET['device']),strval($_GET['serial']),strval($_GET['sw_version']));
		$dbh = null;
		die('false');
	}
	
}else{ // Keine Parameter Uebergeben
	SimpleLogger::info("Bad request\n");
	SimpleLogger::debug("".var_dump($_POST)."\n");
	$dbh = null;
	die('false');
}

$dbh = null; //Datenbankverbindung schließen

	
// ############################################################################################
// Functions ----------------------------------------------------------------------------------
// ############################################################################################

function getFirmware($dbh,$getFirmware){
	try {
		$sql = "SELECT firmware_bin FROM `sw_versions` WHERE software_version=:software_version";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':software_version', $getFirmware);
		$statement->execute();
		$statement->bindColumn(1,$firmware, PDO::PARAM_LOB);
		$statement->fetch(PDO::FETCH_BOUND);
		if(!empty($firmware)){
			header('Content-type: application/octet-stream');
			header('Content-disposition: attachment; filename="firmware_'.$getFirmware.'.bin"');
			header('Content-Transfer-Encoding: binary');
			header("Content-Length: ".strlen($firmware));
			echo($firmware);
		}else{
			SimpleLogger::error("An error has occurred - File '".$getFirmware."' not exist - (getFirmware)\n");
			die('false');			
		}
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (getFirmware)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die('false');
	}
}	

function getSpiffs($dbh,$getSpiffs){
	try {
		$sql = "SELECT spiffs_bin FROM `sw_versions` WHERE software_version=:software_version";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':software_version', $getSpiffs);
		$statement->execute();
		$statement->bindColumn(1,$spiffs, PDO::PARAM_LOB);
		$statement->fetch(PDO::FETCH_BOUND);
		if(!empty($spiffs)){
			header('Content-type: application/octet-stream');
			header('Content-disposition: attachment; filename="spiffs_'.$getSpiffs.'.bin"');
			header('Content-Transfer-Encoding: binary');
			header("Content-Length: ".strlen($spiffs));
			echo($spiffs);
		}else{
			SimpleLogger::error("An error has occurred - File '".$getSpiffs."' not exist - (getFirmware)\n");
			die('false');	
		}
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (getSpiffs)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die('false');
	}		
}	

function searchDevice($dbh,$serial){
	try {
		$sql = "SELECT * FROM `devices` WHERE serial= :serial";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':serial', $serial);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		if ($statement->rowCount() > 0) {
		  return $statement->fetch();
		} else {
		  return false;
		}
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (searchDevice)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die('false');
	}				
}	

function checkUpdate($dbh,$device,$serial,$sw_version){
	try {
		$deviceInfo = searchDevice($dbh,$serial);	
		if($deviceInfo !== false){
			if($deviceInfo['whitelist'] == '1' AND $deviceInfo['device'] == 'nano'){
				//echo 'Betatester';
				$sql = "SELECT software_version FROM `sw_versions` order by software_id desc";
			}elseif($deviceInfo['whitelist'] == '0' AND $deviceInfo['device'] == 'nano'){
				//echo 'Normaler User';
				$sql = "SELECT software_version FROM `sw_versions` WHERE prerelease='0' order by software_id desc";
			}else{
				// Device nicht berechtigt
				die('false');
			}			
			$statement = $dbh->prepare($sql);
			$statement->execute();
			$statement->setFetchMode(PDO::FETCH_ASSOC);
			if($statement !== false){
				$db_version = $statement->fetchAll();
				if (version_compare($db_version['0']['software_version'], $sw_version, ">")) {
					SimpleLogger::info("A new software version is available - '".$db_version['0']['software_version']."' \n");
					header('Content-type: text/html; charset=utf-8');
					header("Content-Length: ".strlen($db_version['0']['software_version']));
					header_remove("Connection: close");
					echo $db_version['0']['software_version'];
					die();
				}else{
					die('false');
					SimpleLogger::info("No update available - '".$sw_version."'/'".$db_version['0']['software_version']."' \n");
				}
			}else{
				die('false');
			}
		}
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (checkUpdate)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die('false');
	}				
}

function updateDevice($dbh,$device,$serial,$name,$hardware_version,$software_version){
	try {
		$sql = "UPDATE `devices` SET device = :device, name = :name, hardware_version = :hardware_version, software_version = :software_version WHERE serial = :serial";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':serial', $serial);
		$statement->bindValue(':device', $device);
		$statement->bindValue(':name', $name);
		$statement->bindValue(':hardware_version', $hardware_version);
		$statement->bindValue(':software_version', $software_version);
		$inserted = $statement->execute();
		if($inserted){
			return true;
		}else{
			return false;
		}
		$statement = null;
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (updateDevice)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die('false');
	}		
}

function insertDevice($dbh,$device,$serial,$name,$hardware_version,$software_version,$update_active,$whitelist){
	try {
		$sql = "INSERT INTO `devices` (`device`,`serial`, `name`, `hardware_version`, `software_version`, `update_active`, `whitelist`) VALUES (:device, :serial, :name, :hardware_version, :software_version, :update_active, :whitelist)";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':device', $device);
		$statement->bindValue(':serial', $serial);
		$statement->bindValue(':name', $name);
		$statement->bindValue(':hardware_version', $hardware_version);
		$statement->bindValue(':software_version', $software_version);
		$statement->bindValue(':update_active', $update_active);
		$statement->bindValue(':whitelist', $whitelist);
		$inserted = $statement->execute();

		// echo $update_active;
		// echo $whitelist;
		if($inserted){
			return true;
		}else{
			return false;
		}
		$statement = null;
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (insertDevice)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die('false');
	}		
}

?>


