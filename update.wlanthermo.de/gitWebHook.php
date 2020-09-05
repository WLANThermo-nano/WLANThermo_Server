<?php
 /*************************************************** 
    Copyright (C) 2020  Florian Riedl
    ***************************
		@author Florian Riedl
		@version 1.1, 05/09/20
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
/**
 * GitHub webhook handler template.
 * 
 * @see  https://developer.github.com/webhooks/
 * @author  Miloslav Hůla (https://github.com/milo)
 */

error_reporting(E_ALL);

// include logging libary 
require_once("../include/SimpleLogger.php"); // logger class
SimpleLogger::$debug = true;

// include database and logfile config
if(stristr($_SERVER['SERVER_NAME'], 'dev-')){
	require_once("../include/dev-db.class.php");
	require_once("../dev-config.inc.php"); // REMOVE
	SimpleLogger::$filePath = '../logs/dev-update.wlanthermo.de/gitWebHook_'.strftime("%Y-%m-%d").'.log';
	SimpleLogger::info("load ../dev-db.class.php\n");
}else{
	require_once("../include/db.class.php");
	require_once("../config.inc.php"); // REMOVE
	SimpleLogger::$filePath = '../logs/update.wlanthermo.de/gitWebHook_'.strftime("%Y-%m-%d").'.log';
	SimpleLogger::info("load ../db.class.php\n");
}	

SimpleLogger::info("############################################################\n");
 
//$hookSecret = '';  # set NULL to disable check
set_error_handler(function($severity, $message, $file, $line) {
	throw new \ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(function($e) {
	header('HTTP/1.1 500 Internal Server Error');
	echo "Error on line {$e->getLine()}: " . htmlSpecialChars($e->getMessage());
	die();
});
$rawPost = NULL;

foreach($hookSecret AS $key) {
	if ($key !== NULL) {
		if (!isset($_SERVER['HTTP_X_HUB_SIGNATURE'])) {
			throw new \Exception("HTTP header 'X-Hub-Signature' is missing.");
		} elseif (!extension_loaded('hash')) {
			throw new \Exception("Missing 'hash' extension to check the secret code validity.");
		}
		list($algo, $hash) = explode('=', $_SERVER['HTTP_X_HUB_SIGNATURE'], 2) + array('', '');
		if (!in_array($algo, hash_algos(), TRUE)) {
			throw new \Exception("Hash algorithm '$algo' is not supported.");
		}
		$rawPost = file_get_contents('php://input');
		
		if ($hash !== hash_hmac($algo, $rawPost, $key)) {
			throw new \Exception('Hook secret does not match.');
		}
	}
}

if (!isset($_SERVER ['CONTENT_TYPE'])) {
	throw new \Exception("Missing HTTP 'Content-Type' header.");
} elseif (!isset($_SERVER['HTTP_X_GITHUB_EVENT'])) {
	throw new \Exception("Missing HTTP 'X-Github-Event' header.");
}

switch ($_SERVER ['CONTENT_TYPE']) {
	case 'application/json':
		$json = $rawPost ?: file_get_contents('php://input');
		break;
	case 'application/x-www-form-urlencoded':
		$json = $_POST['payload'];
		break;
	default:
		throw new \Exception("Unsupported content type: $_SERVER[HTTP_CONTENT_TYPE]");
}

$JsonArr = array();
$JsonArr = json_decode($json, true);
if(isset($JsonArr['zen'])){
	echo "Pong";
	die();
}

$err_flag = false;
switch ($JsonArr['action']) {
	case 'published':
		//SimpleLogger::debug("case published\n");
		foreach ($JsonArr['release']['assets'] as $key => $inhalt){
			$arr = explode('_',pathinfo($inhalt['name'])['filename']);
			$file = false;

			try {
				$file = file_get_contents(strval($inhalt['browser_download_url']), true);
			} catch (Exception $e) {
				$err_flag = true;
			}
			//SimpleLogger::debug("foreach\n");
			if(count($arr) == 5 AND $file !== false){
				//SimpleLogger::debug("validation true\n");
				$db = new DB();
				$sql = "INSERT INTO software_files (device, hardware_version, cpu, software_version, release_id, asset_id, prerelease, file_type, file_url, file_name,file_sha256, file) 
						VALUES (:device, :hardware_version, :cpu, :software_version, :release_id, :asset_id, :prerelease, :file_type, :file_url, :file_name,:file_sha256, :file)";
				$statement = $db->connect()->prepare($sql);
				$statement->bindValue(':device', $arr[0]);
				$statement->bindValue(':hardware_version', $arr[1]);
				$statement->bindValue(':cpu', $arr[2]);
				$statement->bindValue(':software_version', $arr[4]);
				$statement->bindValue(':release_id', $JsonArr["release"]["id"]);
				$statement->bindValue(':asset_id', $inhalt['id']);
				$statement->bindValue(':prerelease', $JsonArr["release"]["prerelease"]);
				$statement->bindValue(':file_type', $arr[3]);
				$statement->bindValue(':file_url', $inhalt['browser_download_url']);
				$statement->bindValue(':file_name', $inhalt['name']);
				$statement->bindValue(':file_sha256', hash('sha256',$file));
				$statement->bindValue(':file', $file, PDO::PARAM_LOB);
				$inserted = $statement->execute();
				if($inserted){
					echo "File '".$inhalt['name']."' was imported into the database";
				}else{
					echo "false";
					$err_flag = true;
				}				
			}else{
				echo "Filename '".$inhalt['name']."' or file not valid";
				$err_flag = true; 
			}
		}
		SimpleLogger::debug("action published found\n");
		SimpleLogger::debug($json);
		break;
	case 'deleted':
		$db = new DB();
		$sql = "UPDATE software_files SET active='0' WHERE release_id= :release_id";
		$statement = $db->connect()->prepare($sql);
		$statement->bindValue(':release_id', $JsonArr["release"]["id"]);		
		$inserted = $statement->execute();
		if($inserted){
			echo "true";
			SimpleLogger::debug("Delete true");
		}else{
			echo "false";
			SimpleLogger::debug("Delete false");
		}		
	
		break;
	case 'released':
	case 'prereleased':
		$db = new DB();
		$sql = "UPDATE software_files SET prerelease= :prerelease WHERE release_id= :release_id";
		$statement = $db->connect()->prepare($sql);
		$statement->bindValue(':release_id', $JsonArr["release"]["id"]);
		$statement->bindValue(':prerelease', $JsonArr["release"]["prerelease"]);
		$inserted = $statement->execute();
		if($inserted){
			echo "true";
			SimpleLogger::debug("Edit true");
		}else{
			echo "false";
			SimpleLogger::debug("Edit false");
		}

		break;
	case 'edited':
		echo "action edited - nothing todo...";
		break;
	default:
		header('HTTP/1.0 404 Not Found');
		SimpleLogger::debug("action published not found\n");
		SimpleLogger::debug($json);
		echo "Event:$_SERVER[HTTP_X_GITHUB_EVENT] Payload:\n";
		print_r($JsonArr); # For debug only. Can be found in GitHub hook log.
		die();
}

if($err_flag){
	$db = new DB();
	$sql = "DELETE FROM software_files WHERE release_id= :release_id";
	$statement = $db->connect()->prepare($sql);
	$statement->bindValue(':release_id', $JsonArr["release"]["id"]);
	$inserted = $statement->execute();
	if($inserted){
		echo "All database entries with releaseID '".$JsonArr["release"]["id"]."' have been deleted\n";
		SimpleLogger::debug("All database entries with releaseID '".$JsonArr["release"]["id"]."' have been deleted\n");
	}else{
		echo "The database entries could not be deleted. Please delete manually!\n";
		SimpleLogger::debug("The database entries could not be deleted. Please delete manually!\n");
	}
	header('HTTP/1.0 404 Not Found');
}