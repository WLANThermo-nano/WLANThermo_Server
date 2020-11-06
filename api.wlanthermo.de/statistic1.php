<?php
 /*************************************************** 
    Copyright (C) 2020  Florian Riedl
    ***************************
		@author Florian Riedl
		@version 1.0, 05/09/20
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
 
//-----------------------------------------------------------------------------
// error reporting
 error_reporting(E_ALL); 
//-----------------------------------------------------------------------------
// start runtome counter
 $time_start = microtime(true);
//-----------------------------------------------------------------------------
// include logging libary 
require_once("../include/SimpleLogger.php"); // logger class
SimpleLogger::$debug = true;

// include database and logfile config
if(stristr($_SERVER['SERVER_NAME'], 'dev-')){
	require_once("../include/dev-db.class.php");
	require_once("../dev-config.inc.php"); // REMOVE
	SimpleLogger::$filePath = '../logs/dev-api.wlanthermo.de/statistic_'.strftime("%Y-%m-%d").'.log';
	SimpleLogger::info("load ../dev-db.class.php\n");
}else{
	require_once("../include/db.class.php");
	require_once("../config.inc.php"); // REMOVE
	SimpleLogger::$filePath = '../logs/api.wlanthermo.de/statistic_'.strftime("%Y-%m-%d").'.log';
	SimpleLogger::info("load ../db.class.php\n");
}	

$output = array();
// main 

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

$stmt = $dbh->prepare("SELECT COUNT(DISTINCT(serial))
						FROM cloud
						WHERE `time` >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
					"); 
$stmt->execute(); 
$row = $stmt->fetch();
$output['statistic']['online'] = $row[0];	
	
$stmt = $dbh->prepare("SELECT device, hardware_version,COUNT(*)
					   FROM devices 
					   GROUP BY device, hardware_version ORDER by device asc 
					  "); 
$stmt->execute(); 
$row = $stmt->fetchAll();
		
foreach ($row as &$key) {
	$output['statistic'][''.$key['device'].'_'.$key['hardware_version'].''] = $key['COUNT(*)'];
}
		
header('Content-type:application/json;charset=utf-8');	
echo json_encode($output, JSON_UNESCAPED_SLASHES);