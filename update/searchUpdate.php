<?php
error_reporting(E_ALL);
require_once("../../config.inc.php");
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
		$sql = "INSERT INTO `sw_versions` (`software_version`, `software_id`, `prerelease`, `firmware_url`, `spiffs_url`, `firmware_bin`, `spiffs_bin`, `ts_insert`) VALUES (:software_version, :software_id, :prerelease, :firmware_url, :spiffs_url, :firmware_bin, :spiffs_bin, :ts_insert)";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':software_version', $software_version);
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
  //private static $filePath = 'html/logs/searchUpdate.log';
  //private static $filePath = 'html/logs/strftime'.("%Y%m%d").'searchUpdate.log';	 
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
	$filePath = 'html/logs/'.strftime("%Y-%m-%d").'_update.log';
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
	$filePath = 'html/logs/'.strftime("%Y-%m-%d").'_update.log';
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


