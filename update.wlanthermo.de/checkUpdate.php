<?php
 /*************************************************** 
    Copyright (C) 2020  Florian Riedl
    ***************************
		@author Florian Riedl
		@version 1.0, 12/02/20
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
 /*Example:
 *
 * Check for new update
 * 		http://nano.wlanthermo.de/checkUpdate.php?device="nano"&serial="Serialnummer"&hw_version="v1"&sw_version="currentVersion"
 * ----------------------------------------------------------------------------------------------------------------------------------------
 * Download Firmware-version XYZ 
 * 		http://nano.wlanthermo.de/checkUpdate.php?device="nano"serial="Serialnummer"&hw_version="v1"&sw_version="currentVersion"&getFirmware="XYZ"
 * ----------------------------------------------------------------------------------------------------------------------------------------
 * Download Spiffs-version XYZ 
 * 		http://nano.wlanthermo.de/checkUpdate.php?device="nano"serial="Serialnummer"&hw_version="v1"&sw_version="currentVersion"&getSpiffs="XYZ"
 * ---------------------------------------------------------------------------------------------------------------------------------------- 
 */	
 
//-----------------------------------------------------------------------------
// error reporting
error_reporting(E_ALL); 
//-----------------------------------------------------------------------------
// include database libary
require_once("../include/db.class.php");
//-----------------------------------------------------------------------------
// include device libary
require_once("../include/device.class.php");
//-----------------------------------------------------------------------------
// include device libary
require_once("../include/asset.class.php");
//-----------------------------------------------------------------------------

if(checkDevice()){
	$device = new Device($_GET['device'], $_GET['serial'], $_GET['hw_version'], $_GET['sw_version']);
	$asset = new asset();
}else{
    http_response_code(400); // Bad request
	exit;
}

if (isset($_GET['getFirmware']) AND !empty($_GET['getFirmware'])){
	$return = $device->getSoftwareByVersion($_GET['getFirmware']);
	$keys = array_keys($return);
	for($i = 0; $i < count($return); $i++) {
		if($return[$keys[$i]]['file_type'] == 'firmware'){
			$asset->setAssetId($return[$keys[$i]]['asset_id']);
			$file = $asset->getFile();
			$filename = $asset->getFileName();
			if(!$filename){
				$filename = "filename.bin";
			}
			if($file){
				header('Content-disposition: attachment; filename='.$filename.'');
				header('Content-Transfer-Encoding: binary');
				header("Content-Length: ".strlen($file));
				echo($file);
			}					
		}		
	}
}elseif (isset($_GET['getSpiffs']) AND !empty($_GET['getSpiffs'])){
	$return = $device->getSoftwareByVersion($_GET['getSpiffs']);
	$keys = array_keys($return);
	for($i = 0; $i < count($return); $i++) {
		if($return[$keys[$i]]['file_type'] == 'spiffs'){
			$asset->setAssetId($return[$keys[$i]]['asset_id']);
			$file = $asset->getFile();
			$filename = $asset->getFileName();
			if(!$filename){
				$filename = "filename.bin";
			}
			if($file){
				header('Content-disposition: attachment; filename='.$filename.'');
				header('Content-Transfer-Encoding: binary');
				header("Content-Length: ".strlen($file));
				echo($file);
			}					
		}		
	}
}else{
	header("Content-Length: ".strlen($device->getSoftwareUpdate()[0]['software_version']));
	echo $device->getSoftwareUpdate()[0]['software_version'];
}

	
// ############################################################################################
// Functions ----------------------------------------------------------------------------------
// ############################################################################################
function checkDevice(){
	if (isset($_GET['device']) AND !empty($_GET['device']) AND 
		isset($_GET['serial']) AND !empty($_GET['serial']) AND 
		isset($_GET['hw_version']) AND !empty($_GET['hw_version']) AND 
		isset($_GET['sw_version']) AND !empty($_GET['sw_version']))
	{
		return true;
	}else{
		return false;
	}	
}
?>


