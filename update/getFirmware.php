<?php
error_reporting(E_ALL);

/* @author Florian Riedl
 * @version 0.1, 24/02/18 
  * Example:
 * ----------------------------------------------------------------------------------------------------------------------------------------
 * Download Firmware-version XYZ 
 * 		http://update.wlanthermo.de/getFirmware.php?device=nano&serial=xxxxxx&firmware=v0.0.0
 * ----------------------------------------------------------------------------------------------------------------------------------------
 */	
 
//-----------------------------------------------------------------------------
// include Logging libary 
$logfile = '_getFirmware.log'; // global var for logger class filename
$logpath = '../logs/';  // global var for logger class filepath
require_once("../include/logger.php"); // logger class
//-----------------------------------------------------------------------------
// include database config
require_once("../../config.inc.php"); // 
//-----------------------------------------------------------------------------
// main 

if (isset($_GET['device']) AND !empty($_GET['device']) AND isset($_GET['serial']) AND !empty($_GET['serial']) AND isset($_GET['version']) AND !empty($_GET['version'])){

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
	// load Firmware from database
	try {
		$sql = "SELECT firmware_bin FROM `sw_versions` WHERE software_version=:software_version AND device=:device";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':software_version', $_GET['version']);
		$statement->bindValue(':device', $_GET['device']);
		$statement->execute();
		$statement->bindColumn(1,$firmware, PDO::PARAM_LOB);
		$statement->fetch(PDO::FETCH_BOUND);
		if(!empty($firmware)){
			header('Content-type: application/octet-stream');
			header('Content-disposition: attachment; filename="firmware_'.$_GET['version'].'.bin"');
			header('Content-Transfer-Encoding: binary');
			header("Content-Length: ".strlen($firmware));
			echo($firmware);
		}else{
			SimpleLogger::error("An error has occurred - File '".$_GET['version']."' not exist - (getFirmware)\n");
			die('false');			
		}
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (getFirmware)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die('false');
	}
	SimpleLogger::info("Device '".$_GET['device']."/".$_GET['serial']."' download firmware version - ".$_GET['version']."\n");		
	$dbh = null; //Datenbankverbindung schließen
}else{ // Keine Parameter Uebergeben
	SimpleLogger::info("-----------------------------------\n");
	SimpleLogger::info("Bad request\n");
	SimpleLogger::debug("".var_dump($_POST)."\n");
	die('false');
}
?>


