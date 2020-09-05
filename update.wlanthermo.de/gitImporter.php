<?php
 /*************************************************** 
    Copyright (C) 2020  Florian Riedl
    ***************************
		@author Florian Riedl
		@version 0.1, 08/02/20
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
	 
require_once("../include/SimpleLogger.php"); // logger class
require_once("../include/db.class.php");
require_once("../config.inc.php");

SimpleLogger::$filePath = '../logs/update.wlanthermo.de/gitImporter_'.strftime("%Y-%m-%d").'.log';
SimpleLogger::$debug = true;
SimpleLogger::info("############################################################\n");
SimpleLogger::info("starting update script...\n");
//$githubApiUrl = 'https://api.github.com/repos/WLANThermo-nano/WLANThermo_nano_Software/releases?per_page=100';
$githubApiUrl = 'https://api.github.com/repos/WLANThermo-nano/WLANThermo_ESP32_Software/releases?per_page=100';

// read Json from GIT
SimpleLogger::info("Download JSON from GITHUB...\n");
$result = getGithubJson($githubApiUrl);
//echo "Start Github import<br>";
if($result !== false){
	$result = array_reverse($result);
	foreach ($result as $key => $inhalt1){
		$err_flag = false;
		// SimpleLogger::info("a new version is available - ".$inhalt['tag_name']."\n");
		SimpleLogger::info("Firmware/Spiffs will be downloaded\n");
		foreach ($inhalt1['assets'] as $key => $inhalt){
			$arr = explode('_',pathinfo($inhalt['name'])['filename']);
			$file = false;
			//echo "File '".$inhalt['name']."' try imported into the database<br>";
			try {
				$file = file_get_contents(strval($inhalt['browser_download_url']), true);

			} catch (Exception $e) {
				$err_flag = true;
				SimpleLogger::info("Exception\n");
			}
			//SimpleLogger::debug("foreach\n");
			if(count($arr) == 5 AND $file !== false){
				SimpleLogger::debug("validation true\n");
				$db = new DB();
				$sql = "INSERT INTO software_files (device, hardware_version, cpu, software_version, release_id, asset_id, prerelease, file_type, file_url, file_name,file_sha256, file) 
						VALUES (:device, :hardware_version, :cpu, :software_version, :release_id, :asset_id, :prerelease, :file_type, :file_url, :file_name,:file_sha256, :file)";
				$statement = $db->connect()->prepare($sql);
				$statement->bindValue(':device', $arr[0]);
				$statement->bindValue(':hardware_version', $arr[1]);
				$statement->bindValue(':cpu', $arr[2]);
				$statement->bindValue(':software_version', $arr[4]);
				$statement->bindValue(':release_id', $inhalt1["id"]);
				$statement->bindValue(':asset_id', $inhalt['id']);
				$statement->bindValue(':prerelease', $inhalt1["prerelease"]);
				$statement->bindValue(':file_type', $arr[3]);
				$statement->bindValue(':file_url', $inhalt['browser_download_url']);
				$statement->bindValue(':file_name', $inhalt['name']);
				$statement->bindValue(':file_sha256', hash('sha256',$file));
				$statement->bindValue(':file', $file, PDO::PARAM_LOB);
				$inserted = $statement->execute();
				if($inserted){
					//echo "File '".$inhalt['name']."' was imported into the database<br>";
				}else{
					//echo "false<br>";
					$err_flag = true;
				}				
			}else{
				echo "Filename '".$inhalt['name']."' or file not valid<br>";
				$err_flag = true; 
			}
		}		
	}

}else{
	SimpleLogger::error("JSON could not be downloaded!\n");
	die('false');
}


	
// ############################################################################################
// Functions ----------------------------------------------------------------------------------
// ############################################################################################

function getGithubJson($githubApiUrl){
	//$passwd = 'MyNano85';
	//$usernamearray = array("nanoUpdate");
	//$usernamekey = array_rand($usernamearray, 1);
	$ch = curl_init();
	// Disable SSL verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// Will return the response, if false it print the response
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Set the url
	curl_setopt($ch, CURLOPT_URL, $githubApiUrl);
	//curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	//curl_setopt($ch, CURLOPT_USERPWD, "$usernamearray[$usernamekey]:$passwd");
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: ESP8285'));
	// Execute
	$result = curl_exec($ch);
	if($result === false){
		SimpleLogger::error("JSON could not be downloaded!\n");
		SimpleLogger::debug("".curl_error($ch)."\n");
		return false;
	}
	$result = json_decode($result,TRUE);
	if (isset($result[0]['tag_name'])){
		SimpleLogger::info("JSON has been downloaded\n");
		return $result;
	}else{
		SimpleLogger::error("JSON could not be downloaded! - ".$result[0]['tag_name']."\n");
		SimpleLogger::debug("".curl_error($ch)."\n");
		return false;
	}
	curl_close($ch);
}