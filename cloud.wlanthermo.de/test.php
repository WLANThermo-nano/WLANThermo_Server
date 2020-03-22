<?php
//-----------------------------------------------------------------------------
// include logging libary 
require_once("../include/SimpleLogger.php"); // logger class
//-----------------------------------------------------------------------------
SimpleLogger::$filePath = '../logs/dev-cloud.wlanthermo.de/test_'.strftime("%Y-%m-%d").'.log';
SimpleLogger::$debug = true;
SimpleLogger::$info = false;
SimpleLogger::$notice = false;
SimpleLogger::$warning = false;
SimpleLogger::$error = false;
SimpleLogger::$critical = false;
SimpleLogger::$alert = false;
SimpleLogger::$emergency = false;

SimpleLogger::debug("Debug\n");
SimpleLogger::info("Info\n");
SimpleLogger::notice("Notice\n");
SimpleLogger::warn("Warning\n");
SimpleLogger::error("Error\n");
SimpleLogger::crit("Critical\n");
SimpleLogger::alert("Alert\n");
SimpleLogger::emerg("Emergency\n");

?>