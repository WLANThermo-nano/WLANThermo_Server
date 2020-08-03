<?php
 /*************************************************** 
    Copyright (C) 2020  Florian Riedl
    ***************************
		@author Florian Riedl
		@version 1.0, 03/07/20
	***************************
	This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
    
    HISTORY: Please refer Github History
    
 ****************************************************/
 
error_reporting(E_ALL);

// include logging libary 



// include database and logfile config

require_once('dev-config.inc.php'); // REMOVE
require_once('include/SimpleLogger.php'); // logger class
SimpleLogger::$filePath = 'var/www/vhosts/api.wlanthermo.de/cleanCloudDB_'.strftime("%Y-%m-%d").'.log';
SimpleLogger::$debug = true;
SimpleLogger::info("------------------------------------------------------------\n");
SimpleLogger::info("starting clean process...\n");

// Connecting to database
try {
	//SimpleLogger::info("Connecting to the database...\n");
	$dbh = new PDO(sprintf('mysql:host=%s;dbname=%s', $db_server, $db_name), $db_user, $db_pass);
	$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
	$dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
} catch (PDOException $e) {
	SimpleLogger::error("An error has occurred\n");
	SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
	die('false');
}
cleanCloud($dbh);
$dbh = null; //Datenbankverbindung schließen
	
// ############################################################################################
// Functions ----------------------------------------------------------------------------------
// ############################################################################################

function cleanCloud($dbh){
	try {
		$sql = "DELETE FROM `cloud` WHERE TIMESTAMP(DATE_SUB(NOW(), INTERVAL 48 hour)) > `time`";
		$statement = $dbh->prepare($sql);
		$inserted = $statement->execute();
		//echo $statement->rowCount(); 
		SimpleLogger::info("".$statement->rowCount()." entries have been deleted\n");
		echo $statement->rowCount();
		if($inserted){
			return true;
		}else{
			return false;
		}
		$statement = null;
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (cleanCloudDB)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die('false');
	}		
}
?>


