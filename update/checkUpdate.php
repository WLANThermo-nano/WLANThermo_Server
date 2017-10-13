<?php
error_reporting(E_ALL);
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

// Needed by strftime() => error message otherwise
date_default_timezone_set ( 'Europe/Berlin' );
 
/**
 * A simple logging class
 *
 * - uses file access to log any message such as debug, info, error,
 * fatal or an exception including stacktrace
 *
 * @author saftmeister
 */
class SimpleLogger
{
  const DEBUG = 1;
  const INFO = 2;
  const NOTICE = 4;
  const WARNING = 8;
  const ERROR = 16;
  const CRITICAL = 32;
  const ALERT = 64;
  const EMERGENCY = 128;
 
  /**
   * Path to log file
   *
   * @var string
   */
	 
  /**
   * Log file size in MB
   *
   * @var number
   */
  private static $maxLogSize = 2;
 
  /**
   * Logs a particular message using a given log level
   *
   * @param number $level
   *          The level of error the message is
   * @param string $message
   *          Either a format or a constant
   *          string which represents the message to log.
   */
  public static function log($level, $message /*,...*/)
  {
	clearstatcache ();
	$filePath = '../logs/'.strftime("%Y-%m-%d").'_device.log';
	if (! is_int ( $level ))
	{
	  $message = $level;
	  $level = self::DEBUG;
	}
	else if ($level != self::DEBUG && $level != self::INFO && $level != self::NOTICE &&
		$level != self::WARNING && $level != self::ERROR && $level != self::CRITICAL &&
		$level != self::ALERT && $level != self::EMERGENCY)
	{
	  $level = self::ERROR;
	}
	$mode = "a";
	if (! file_exists ( $filePath ))
	{
	  $mode = "w";
	}
	else
	{
	  $attributes = stat ( $filePath );
	  if ($attributes == false || $attributes ['size'] >= self::$maxLogSize * 1024 * 1024)
	  {
		$mode = "w";
	  }
	}
   
	$levelStr = "FATAL";
	switch ($level)
	{
	  case self::DEBUG:    $levelStr = "DEBUG"; break;
	  case self::INFO:     $levelStr = "INFO "; break;
	  case self::NOTICE:   $levelStr = "NOTIC"; break;
	  case self::WARNING:  $levelStr = "WARN "; break;
	  case self::ERROR:    $levelStr = "ERROR"; break;
	  case self::CRITICAL: $levelStr = "CRIT "; break;
	  case self::ALERT:    $levelStr = "ALERT"; break;
	  case self::EMERGENCY:$levelStr = "EMERG"; break;
	}
	$filePath = '../logs/'.strftime("%Y-%m-%d").'_device.log';
	$fd = fopen ( $filePath, $mode );
	if ($fd)
	{
	  $arguments = func_get_args ();
	  if (count ( $arguments ) > 2)
	  {
		$format = $arguments [1];
		array_shift ( $arguments ); // Do not need the level
		array_shift ( $arguments ); // Do not need the format as argument
		$message = vsprintf ( $format, $arguments );
	  }
	  $time = strftime ( "%Y-%m-%d %H:%M:%S", time () );
	  fprintf ( $fd, "%s\t[%s]: %s", $time, $levelStr, $message );
	  fflush ( $fd );
	  fclose ( $fd );
	}
  }
 
  /**
   * Simple wrapper arround log method for alert level
   *
   * @param string $message          
   * @see SimpleLogger::log()
   */
  public static function alert($message)
  {
	self::log ( self::ALERT, $message );
  }
 
  /**
   * Simple wrapper arround log method for critical level
   *
   * @param string $message
   * @see SimpleLogger::log()
   */
  public static function crit($message)
  {
	self::log ( self::CRITICAL, $message );
  }
 
  /**
   * Simple wrapper arround log method for debug level
   *
   * @param string $message          
   * @see SimpleLogger::log()
   */
  public static function debug($message)
  {
	self::log ( self::DEBUG, $message );
  }
 
  /**
   * Simple wrapper arround log method for emergency level
   *
   * @param string $message          
   * @see SimpleLogger::log()
   */
  public static function emerg($message)
  {
	self::log ( self::EMERGENCY, $message );
  }
 
  /**
   * Simple wrapper arround log method for info level
   *
   * @param string $message          
   * @see SimpleLogger::log()
   */
  public static function info($message)
  {
	self::log ( self::INFO, $message );
  }
 
  /**
   * Simple wrapper arround log method for notice level
   *
   * @param string $message          
   * @see SimpleLogger::log()
   */
  public static function notice($message)
  {
	self::log ( self::NOTICE, $message );
  }
 
  /**
   * Simple wrapper arround log method for error level
   *
   * @param string $message          
   * @see SimpleLogger::log()
   */
  public static function error($message)
  {
	self::log ( self::ERROR, $message );
  }
 
  /**
   * Simple wrapper arround log method for notice level
   *
   * @param string $message
   * @see SimpleLogger::log()
   */
  public static function warn($message)
  {
	self::log ( self::WARNING, $message );
  }
 
 
  /**
   * Log a particular exception
   *
   * @param Exception $ex
   *          The exception to log
   */
  public static function logException(Exception $ex)
  {
	$level = self::ERROR;
	if ($ex instanceof RuntimeException)
	{
	  $level = self::ALERT;
	}
   
	self::log ( $level, "Exception %s occured: %s\n%s\n", get_class ( $ex ), $ex->getMessage (), $ex->getTraceAsString () );
   
	if ($ex->getPrevious () && $ex->getPrevious () instanceof Exception)
	{
	  self::log ( $level, "Caused by:\n" );
	  self::logException ( $ex->getPrevious () );
	}
  }
 
  /**
   * Dump a particular object and write it to log file
   *
   * @param mixed $o
   */
  public static function dump($o)
  {
	$out = var_export ( $o, true );
	self::debug ( sprintf ( "Contents of %s\n%s\n", gettype ( $o ), $out ) );
  }
}
?>


