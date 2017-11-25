<?php
error_reporting(E_ALL);
require_once("../../config.inc.php");
/* @author Florian Riedl
 */	

SimpleLogger::info("############################################################\n");
ob_start('ob_gzhandler');
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
	
$api_token = '';
$ajson = file_get_contents('php://input');
SimpleLogger::debug("".$ajson."\n");
$json = json_decode( $ajson, true );
if($json['context']['System']['user']['userId'] == 'amzn1.ask.account.AF53TRZJOXEUM42TT37HJ3ZVTWMFAQ2RVZR3GB7WKVQWW53ZFY3ZTOJIHIDO33Q6FXMQ4SZOD4EE7Q4E6VKKEHI3FWP24F7UOJDOZVICRXVVHHHZW7WOCT5XMHRRAHL2WKT7SE2XHP5ZCZYSHJCLXKMCZO5ZQ5SI5S4JDG3SS3OFMJR32YVLLLQ4CRV4QL6ZRWHRMYGGQOWX6QI'){
	$api_token = '84d1ac5a14be11f1';
}else if($json['context']['System']['user']['userId'] == 'amzn1.ask.account.AHRWVU7HQMN7QWKRRYGEVQ65CUVC53W36ITO34I2BWIQLVJH7DFYJ73MBRZCFJPPMVVOVLMVMPM73TVWOQ526J623PDMIV44ZGAT5NNNVDHHW4NXZ6VPQT75MMBJCHWSVWN6P74QAV3U35QXWDYHUCMBMMQKJ3EEI4ISV26TBBPPPRONXUCECOIKGSCIW7SHDBQZGJ274QAIPVQ'){
	$api_token = '82e49e0319aa1623';	
}else{
	$api_token = '84d1ac5a14be11f1';
}

$responseArray = [
            'version' => '1.0',
            'response' => [
                  'outputSpeech' => [
                        'type' => 'PlainText',
                        'text' => 'Hallo, ich bin Alexa und dein Griller gehört jetzt mir.',
                        'ssml' => null
                  ],
				  'card' => [
						'type' => 'Simple',
						'title' => 'Aktivierung',
						'content' => 'Bitte aktiviere deinen Skill.'
				  ],
                  'shouldEndSession' => true
            ]
		];
	  
function getData($dbh,$api_token){
	try {
		$sql = "SELECT data FROM `cloud` WHERE api_token= :api_token ORDER BY id DESC LIMIT 1";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':api_token', $api_token);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		if ($statement->rowCount() > 0) {
		  return($statement->fetch()['data']);
		} else {
		  return false;
		}
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (getData)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die('false');
	}
}	
$data = getData($dbh,$api_token);
$arr = json_decode( $data, true );

switch ($json['request']['intent']['name']) {
    case 'nanoAll':
		if ($arr['channel'][0]['temp'] == 999){
			$responseArray['response']['outputSpeech']['text'] = 'Aktuell ist auf Kanal 1 kein Fühler angeschlossen';
		}else if($arr['channel'][0]['temp'] < 5){
			$responseArray['response']['outputSpeech']['text'] = 'Kanal 1 ist arsch kalt.';
		}else{
			$responseArray['response']['outputSpeech']['text'] = 'Deine Temperatur auf Kanal 1 beträgt '.str_replace(".", ",",$arr['channel'][0]['temp']).' Grad.';
		}   
		break;
	case 'nanoCH':
		if($arr['channel'][$json['request']['intent']['slots']['number']['value'] - 1]['temp'] == 999){
			$responseArray['response']['outputSpeech']['text'] = 'Aktuell ist auf Kanal '.$json['request']['intent']['slots']['number']['value'].' kein Fühler angeschlossen.';
		}else{
			$responseArray['response']['outputSpeech']['text'] = 'Deine Temperatur auf Kanal '.$json['request']['intent']['slots']['number']['value'].' beträgt '.str_replace('.', ',',$arr['channel'][$json['request']['intent']['slots']['number']['value'] - 1]['temp']).' Grad.';
		}
		//if($json['request']['intent']['slots']['number']['value']
        break;
    case 'nanoBattery':
        $responseArray['response']['outputSpeech']['text'] = 'Der Akkustand beträgt '.$arr['system']['soc'].' %';
        break;
	case 'nanoCHname':
        $responseArray['response']['outputSpeech']['text'] = 'Dieser Dienst ist nich verfügbar';
        break;
	default:
		if ($arr['channel'][0]['temp'] == 999){
			$responseArray['response']['outputSpeech']['text'] = 'Aktuell ist auf Kanal 1 kein Fühler angeschlossen';
		}else if($arr['channel'][0]['temp'] < 5){
			$responseArray['response']['outputSpeech']['text'] = 'Kanal 1 ist arsch kalt.';
		}else{
			$responseArray['response']['outputSpeech']['text'] = 'Deine Temperatur auf Kanal 1 beträgt '.str_replace(".", ",",$arr['channel'][0]['temp']).' Grad.';
		}
		break;
}








header ( 'Content-Type: application/json' );
echo json_encode ( $responseArray );
 

 
 
 
 
 
 
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
  private static $filePath = 'html/logs/alexa.log';
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
	$filePath = '../logs/'.strftime("%Y-%m-%d").'_alexa.log';
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
	$filePath = '../logs/'.strftime("%Y-%m-%d").'_alexa.log';
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


 
?>

