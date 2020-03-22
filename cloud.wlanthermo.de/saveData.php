<?php
 /*************************************************** 
    Copyright (C) 2020  Florian Riedl
    ***************************
		@author Florian Riedl
		@version 1.0, 22/03/20
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
 
// error reporting
error_reporting(E_ALL); 

// include logging libary 
require_once("../include/SimpleLogger.php"); // logger class
SimpleLogger::$debug = true;

// include database and logfile config
if(stristr($_SERVER['SERVER_NAME'], 'dev-')){
	require_once("../include/dev-db.class.php");
	require_once("../dev-config.inc.php"); // REMOVE
	SimpleLogger::$filePath = '../logs/dev-cloud.wlanthermo.de/saveData_'.strftime("%Y-%m-%d").'.log';
	SimpleLogger::info("load ../dev-db.class.php\n");
}else{
	require_once("../include/db.class.php");
	require_once("../config.inc.php"); // REMOVE
	SimpleLogger::$filePath = '../logs/cloud.wlanthermo.de/saveData_'.strftime("%Y-%m-%d").'.log';
	SimpleLogger::info("load ../db.class.php\n");
}	

// include cloud libary
require_once("../include/cloud.class.php");
$cloud = new Cloud();

// read input data
$json = file_get_contents('php://input');

// decode & check input data
$JsonArr = json_decode( $json, true );

if (($JsonArr === null && json_last_error() !== JSON_ERROR_NONE)) {
    http_response_code(400); // Bad request
	SimpleLogger::error("JSON invalide\n");
	SimpleLogger::dump($json . "\n");
	exit;
}

if (isset($_GET['serial']) AND !empty($_GET['serial']) AND isset($_GET['api_token']) AND !empty($_GET['api_token'])){
	if($JsonArr['system']['time'] <= '1483228800'){
		$JsonArr['system']['time'] = time();
	}
	$data[0] = $JsonArr;
	http_response_code(200);
	echo $cloud->insertCloudData($_GET['serial'],$_GET['api_token'],$data) ? 'true' : 'false';
	exit;
}else if (isset($JsonArr['system']['serial']) AND isset($JsonArr['system']['api_token'])){
	if($JsonArr['system']['time'] <= '1483228800'){
		$JsonArr['system']['time'] = time();
	}
	$serial = $JsonArr['system']['serial'];
	$api_token = $JsonArr['system']['api_token'];
	unset($JsonArr['system']['serial']);
	unset($JsonArr['system']['api_token']);
	$data[0] = $JsonArr;
	http_response_code(200);
	echo $cloud->insertCloudData($serial,$api_token,$data) ? 'true' : 'false';
	exit;
}else{
	http_response_code(400); // Bad request
	echo "Serial or API_Token not set";
	SimpleLogger::error("Serial or API_Token not set\n");
	SimpleLogger::dump($json . "\n");
	exit;
}

?>


