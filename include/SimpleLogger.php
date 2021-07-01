<?php
// Needed by strftime() => error message otherwise
date_default_timezone_set ( 'Europe/Berlin' );

/**
 * A simple logging class
 *
 * - uses file access to log any message such as debug, info, error,
 * fatal or an exception including stacktrace
 *
 * @author saftmeister
 * modified @Florian R.
 */
 
/*
* Example
SimpleLogger::$debug = true;
SimpleLogger::$filePath = 'test_'.strftime("%Y-%m-%d").'.log';

SimpleLogger::debug("An error has occurred\n");
SimpleLogger::info("An error has occurred\n");
SimpleLogger::notice("An error has occurred\n");
SimpleLogger::warn("An error has occurred\n");
SimpleLogger::error("An error has occurred\n");
SimpleLogger::crit("An error has occurred\n");
SimpleLogger::alert("An error has occurred\n");
SimpleLogger::emerg("An error has occurred\n"); 
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
  
  public static $debug = true;
  public static $info = true;
  public static $notice = true;
  public static $warning = true;
  public static $error = true;
  public static $critical = true;
  public static $alert = true;
  public static $emergency = true;
  
  /**
   * Path to log file
   *
   * @var string
   */
  public static $filePath = 'logfile.log';
  
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
    
    if (! is_int ( $level ))
    {
      $message = $level;
      $level = self::DEBUG;
    }
	elseif ( $level & 255 === 0 ) {
		$level = self::ERROR;
	}
    $mode = "a";
    if (! file_exists ( self::$filePath ))
    {
      $mode = "w";
    }
    else
    {
      $attributes = stat ( self::$filePath );
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
    
    /*$fd = fopen ( self::$filePath, $mode );
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
    }*/

  }
  
  /**
   * Simple wrapper arround log method for alert level
   *
   * @param string $message          
   * @see SimpleLogger::log()
   */
  public static function alert($message)
  {
	if(self::$alert){  
		self::log ( self::ALERT, $message );
	}
  }
  
  /**
   * Simple wrapper arround log method for critical level
   *
   * @param string $message
   * @see SimpleLogger::log()
   */
  public static function crit($message)
  {
	if(self::$critical){  
		self::log ( self::CRITICAL, $message );
	}
  }
  
  /**
   * Simple wrapper arround log method for debug level
   *
   * @param string $message          
   * @see SimpleLogger::log()
   */
  public static function debug($message)
  {
	if(self::$debug){  
		self::log ( self::DEBUG, $message );
	}
  }
  
  /**
   * Simple wrapper arround log method for emergency level
   *
   * @param string $message          
   * @see SimpleLogger::log()
   */
  public static function emerg($message)
  {
	if(self::$emergency){  
		self::log ( self::EMERGENCY, $message );
	}
  }
  
  /**
   * Simple wrapper arround log method for info level
   *
   * @param string $message          
   * @see SimpleLogger::log()
   */
  public static function info($message)
  {
	if(self::$info){  
		self::log ( self::INFO, $message );
	}
  }
  
  /**
   * Simple wrapper arround log method for notice level
   *
   * @param string $message          
   * @see SimpleLogger::log()
   */
  public static function notice($message)
  {
	if(self::$notice){    
		self::log ( self::NOTICE, $message );
	}
  }

  /**
   * Simple wrapper arround log method for error level
   *
   * @param string $message          
   * @see SimpleLogger::log()
   */
  public static function error($message)
  {
	if(self::$error){  
		self::log ( self::ERROR, $message );
	}
  }
  
  /**
   * Simple wrapper arround log method for notice level
   *
   * @param string $message
   * @see SimpleLogger::log()
   */
  public static function warn($message)
  {
	if(self::$warning){  
		self::log ( self::WARNING, $message );
	}
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
