<?php
error_reporting(E_ALL);
require_once("../../config.inc.php");
/* @author Florian Riedl
 */	
//SimpleLogger::info("############################################################\n");
$json = file_get_contents('php://input');

if (isset($_GET['serial']) AND !empty($_GET['serial']) AND isset($_GET['api_token']) AND !empty($_GET['api_token'])){
	if(!empty($json)){
		$dbh = connectDatabase($db_server,$db_name,$db_user,$db_pass);
		insertCloud($dbh,$_GET['serial'],$_GET['api_token'],$json);
	}else{
		SimpleLogger:_error("Serial ot API_Token not set\n");
		die('false');
	}//Connecting to database
}else{
	die('false');
}

function connectDatabase($db_server,$db_name,$db_user,$db_pass){
		try {
			SimpleLogger::info("Connecting to the database...\n");
			$dbh = new PDO(sprintf('mysql:host=%s;dbname=%s', $db_server, $db_name), $db_user, $db_pass);
			$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
			$dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			return $dbh;
		} catch (PDOException $e) {
			SimpleLogger::error("An error has occurred\n");
			SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
			die('false');
		}	
}
function closeDatabase(){
	$dbh = null;
}

function insertCloud($dbh,$serial,$api_token,$data){
	try {
		$sql = "INSERT INTO `cloud` (`serial`, `api_token`, `data`) VALUES (:serial, :api_token, :data)";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':serial', $serial);
		$statement->bindValue(':api_token', $api_token);
		$statement->bindValue(':data', $data);
		$inserted = $statement->execute();
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
  private static $filePath = 'html/logs/saveData.log';
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
	$filePath = '../logs/'.strftime("%Y-%m-%d").'_saveData.log';
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
	$filePath = '../logs/'.strftime("%Y-%m-%d").'_saveData.log';
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


