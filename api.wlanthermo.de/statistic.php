<?php
 /*************************************************** 
    Copyright (C) 2018  Florian Riedl
    ***************************
		@author Florian Riedl
		@version 0.3, 12/12/18
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
// include Logging libary 
$logfile = '_api.log'; // global var for logger class filename
$logpath = '../logs/';  // global var for logger class filepath
require_once("../include/logger.php"); // logger class
//-----------------------------------------------------------------------------
// include database config
require_once("../config.inc.php"); // 
//-----------------------------------------------------------------------------
$output = array();
// main 


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
	


		$stmt = $dbh->prepare("SELECT COUNT(DISTINCT(serial))
							   FROM cloud
							   WHERE `time` >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
							  "); 
		$stmt->execute(); 
		$row = $stmt->fetch();
		$output['statistic']['online'] = $row[0];	
	
		$stmt = $dbh->prepare("SELECT COUNT(serial)
							   FROM devices
							   WHERE device='nano' 
							   AND hardware_version='v1'
							  "); 
		$stmt->execute(); 
		$row = $stmt->fetch();
		$output['statistic']['nano_v1'] = $row[0];

		$stmt = $dbh->prepare("SELECT COUNT(serial)
							   FROM devices
							   WHERE device='nano' 
							   AND hardware_version='v2'
							  "); 
		$stmt->execute(); 
		$row = $stmt->fetch();
		$output['statistic']['nano_v2'] = $row[0];
		
		$stmt = $dbh->prepare("SELECT COUNT(serial)
							   FROM devices
							   WHERE device='nano32' 
							  "); 
		$stmt->execute(); 
		$row = $stmt->fetch();
		$output['statistic']['nano_32'] = $row[0];
		
		header('Content-type:application/json;charset=utf-8');	
		echo json_encode($output, JSON_UNESCAPED_SLASHES);

?>