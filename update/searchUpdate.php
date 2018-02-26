<?php
error_reporting(E_ALL);
$logfile = '_update.log'; // global var for logger class filename
$logpath = 'html/logs/';  // global var for logger class filepath
$device = "nano";
require_once("html/include/logger.php");
require_once("/var/www/virtual/nano/config.inc.php");
/* @author Florian Riedl
 *
 */	

SimpleLogger::info("############################################################\n");
SimpleLogger::info("starting update script...\n");
$githubApiUrl = 'https://api.github.com/repos/WLANThermo-nano/WLANThermo_nano_Software/releases';

// Connecting to database
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

// read Json from GIT
SimpleLogger::info("Download JSON from GITHUB...\n");
$result = getGithubJson($githubApiUrl);

// read sw-versions from database
$sw_versions = searchsw_versions($dbh);

if($result !== false && $sw_versions !== false){
	$result = array_reverse($result);
	$found_global = false;
	foreach ($result as $key => $inhalt){
		$found = false;
		foreach ($sw_versions as $sw_key => $sw_inhalt){
			if ($sw_inhalt['software_version'] == $inhalt['tag_name']){
				$found = true;
			}
		}
		if($found === true){
			//found sw-version in database
		}else{
			SimpleLogger::info("a new version is available - ".$inhalt['tag_name']."\n");
			$found_global = true;
			SimpleLogger::info("Firmware/Spiffs will be downloaded\n");
			$firmware = file_get_contents(strval($inhalt['assets'][0]['browser_download_url']), true);
			$spiffs = file_get_contents(strval($inhalt['assets'][1]['browser_download_url']), true);
			
			if ($firmware !== false && $spiffs !== false) {
				SimpleLogger::info("Firmware/Spiffs has been downloaded\n");
				insertVersion($dbh,$inhalt['tag_name'],$inhalt['id'],$inhalt['prerelease'],$inhalt['assets'][0]['browser_download_url'],$inhalt['assets'][1]['browser_download_url'],$firmware,$spiffs);
			}else{
				SimpleLogger::error("Firmware/Spiffs could not be downloaded!\n");
				SimpleLogger::debug("".$inhalt['assets'][0]['browser_download_url']."\n");
				SimpleLogger::debug("".$inhalt['assets'][1]['browser_download_url']."\n");
			}
		}				
	}
	if($found_global === false){
		SimpleLogger::info("no updates available\n");			
	}
}else{
	SimpleLogger::error("JSON could not be downloaded!\n");
	die('false');
}

$dbh = null; //Datenbankverbindung schließen
	
// ############################################################################################
// Functions ----------------------------------------------------------------------------------
// ############################################################################################

function getGithubJson($githubApiUrl){
	//$passwd = 'MyNano85';
	//$usernamearray = array("nanoUpdate");
	//$usernamekey = array_rand($usernamearray, 1);
	$ch = curl_init();
	// Disable SSL verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// Will return the response, if false it print the response
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Set the url
	curl_setopt($ch, CURLOPT_URL, $githubApiUrl);
	//curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	//curl_setopt($ch, CURLOPT_USERPWD, "$usernamearray[$usernamekey]:$passwd");
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: ESP8285'));
	// Execute
	$result = curl_exec($ch);
	if($result === false){
		SimpleLogger::error("JSON could not be downloaded!\n");
		SimpleLogger::debug("".curl_error($ch)."\n");
		return false;
	}
	$result = json_decode($result,TRUE);
	if (isset($result[0]['tag_name'])){
		SimpleLogger::info("JSON has been downloaded\n");
		return $result;
	}else{
		SimpleLogger::error("JSON could not be downloaded! - ".$result[0]['tag_name']."\n");
		SimpleLogger::debug("".curl_error($ch)."\n");
		return false;
	}
	curl_close($ch);
}

function searchsw_versions($dbh){
	try {
		$sql = "SELECT * FROM `sw_versions`";
		$statement = $dbh->prepare($sql);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		return $statement->fetchAll();
		$statement = null;		
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (searchsw_versions)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die();
	}	
}	

function insertVersion($dbh,$software_version,$software_id,$prerelease,$firmware_url,$spiffs_url,$firmware_bin,$spiffs_bin){
	try {
		$sql = "INSERT INTO `sw_versions` (`software_version`, `device`, `software_id`, `prerelease`, `firmware_url`, `spiffs_url`, `firmware_bin`, `spiffs_bin`, `ts_insert`) VALUES (:software_version, :device, :software_id, :prerelease, :firmware_url, :spiffs_url, :firmware_bin, :spiffs_bin, :ts_insert)";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':software_version', $software_version);
		$statement->bindValue(':device', $device);
		$statement->bindValue(':software_id', $software_id);
		$statement->bindValue(':prerelease', $prerelease);
		$statement->bindValue(':firmware_url', $firmware_url);
		$statement->bindValue(':spiffs_url', $spiffs_url);
		$statement->bindValue(':firmware_bin', $firmware_bin, PDO::PARAM_LOB);
		$statement->bindValue(':spiffs_bin', $spiffs_bin, PDO::PARAM_LOB);
		$date = date('Y-m-d H:i:s');
		$statement->bindParam(':ts_insert', $date, PDO::PARAM_STR);
		$inserted = $statement->execute();
		$statement = null;
		if($inserted){
			return true;
		}else{
			return false;
		}
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (insertVersion)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die();
	}
}
?>


