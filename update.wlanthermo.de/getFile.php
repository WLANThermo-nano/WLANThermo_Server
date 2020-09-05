<?php
 /*************************************************** 
    Copyright (C) 2020  Florian Riedl
    ***************************
		@author Florian Riedl
		@version 1.0, 21/04/20
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
// include logging libary 
//require_once("../include/SimpleLogger.php"); // logger class
//-----------------------------------------------------------------------------
// include database libary
require_once("../include/db.class.php");
//-----------------------------------------------------------------------------
// include asset libary
require_once("../include/asset.class.php");
//-----------------------------------------------------------------------------

if (isset($_GET['asset_id']) AND !empty($_GET['asset_id'])){
	$asset = new asset();
	$asset->setAssetId($_GET['asset_id']);
	$file = $asset->getFile();
	$filename = $asset->getFileName();	
	if(!$filename){
		$filename = "filename.bin";
	}
	if($file){
		header("Digest: sha-256=".base64_encode(hex2bin(hash('sha256',$file))));
		if(stripos($_SERVER[HTTP_ACCEPT_ENCODING], "deflate") !== false){
			$file = gzcompress($file, 6);
			header("Content-Encoding: deflate");		
		}
		header('Content-disposition: attachment; filename='.$filename.'');
		header('Content-Transfer-Encoding: binary');
		header("Content-Length: ".strlen($file));
		echo($file);
	}else{
		http_response_code(404); // Not Found
		exit;
	}
}