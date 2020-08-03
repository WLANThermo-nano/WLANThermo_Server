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
require_once("../dev-config.inc.php"); // 
//-----------------------------------------------------------------------------
if (isset($_GET['api_token']) AND !empty($_GET['api_token'])){
	$api_token=$_GET['api_token'];
	try {
		$dbh = new PDO(sprintf('mysql:host=%s;dbname=%s', $db_server, $db_name), $db_user, $db_pass);
		$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
		$dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
		SimpleLogger::error("Database - Connecting true\n");
	} catch (PDOException $e) {
		SimpleLogger::error("Database - An error has occurred\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die('false');
	}
}

$serial = getSerial($dbh,$api_token);
if($serial != false){
	$software_version = getSwVersion($dbh,$serial);
	if($software_version != false){
		$dbVersion = checkVersion($dbh,$serial,$software_version);
		if(dbVersion != false){
			$newVersion = compareVersion($dbVersion, $software_version);
			if($newVersion != false){
				echo $newVersion;
			}else{
				echo 'false';
			}			
		}else{
			echo 'false';
		}
	}else{
		echo 'false';	
	}
}else{
	echo 'false';
}	

function getSwVersion($dbh,$serial){
	try {
		$sql = "SELECT software_version FROM devices where serial= :serial";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':serial', $serial);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		if ($statement->rowCount() > 0) {
		  $deviceInfo = $statement->fetch();
		  //print_r ($deviceInfo);
		  return $deviceInfo['software_version'];
		  
		} else {
			return false;
		}
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (checkNewUpdate)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		//return('false');
	}	
}

function getSerial($dbh,$api_token){
	try {
		$sql = "SELECT serial FROM cloud where api_token= :api_token limit 1 ";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':api_token', $api_token);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		if ($statement->rowCount() > 0) {
			$deviceInfo = $statement->fetch();
			//print_r ($deviceInfo);
			return $deviceInfo['serial'];
		} else {
		  return false;
		}
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (checkNewUpdate)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		//return('false');
	}	
}

function checkVersion($dbh,$serial,$version){
	try {
		$sql = "select s1.software_version from sw_versions as s1, 
				(SELECT d.serial, max(s.software_id) as software_id FROM `devices` as d, sw_versions as s WHERE 
				d.device = s.device and d.update_active = 1 and (d.whitelist = 1 or s.prerelease = 0) and d.serial = :serial
				group by d.serial) as s2
				where 
				s1.software_id = s2.software_id";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':serial', $serial);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		if ($statement->rowCount() > 0) {
		  $deviceInfo = $statement->fetch();
		  return($deviceInfo['software_version']);
		} else {
		  return(false);
		}
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (checkVersion)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		return(false);
	}	
}
//-----------------------------------------------------------------------------
// compare version numbers
function compareVersion($dbVersion, $deviceVersion){
	if (version_compare($dbVersion, $deviceVersion, ">")) {
		return $dbVersion;
	}else{
		return false;
	}
}


?>