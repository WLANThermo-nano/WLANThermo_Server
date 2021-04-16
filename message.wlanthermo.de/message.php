<?php
 /*************************************************** 
    Copyright (C) 2021  Florian Riedl
    ***************************
		@author Florian Riedl
		@version 1.0, 17/01/2021
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
	require_once("../dev-config.inc.php"); // REMOVE
	SimpleLogger::$filePath = '../logs/dev-message.wlanthermo.de/message_'.strftime("%Y-%m-%d").'.log';
}else{
	require_once("../config.inc.php"); // REMOVE
	SimpleLogger::$filePath = '../logs/message.wlanthermo.de/message_'.strftime("%Y-%m-%d").'.log';
}
//-----------------------------------------------------------------------------
// include notification libary
require_once("../include/notification.class.php");

//whether ip is from share internet

if (!empty($_SERVER['HTTP_CLIENT_IP'])){
    $ip_address = $_SERVER['HTTP_CLIENT_IP'];
}elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){ //whether ip is from proxy
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
}else{ //whether ip is from remote address
	$ip_address = $_SERVER['REMOTE_ADDR'];
}

if (isset($_GET['serial']) AND !empty($_GET['serial']) AND isset($_GET['token']) AND !empty($_GET['token']) AND isset($_GET['chatID']) AND !empty($_GET['chatID']) AND isset($_GET['ch']) AND isset($_GET['msg']) AND !empty($_GET['msg']) AND isset($_GET['lang']) AND !empty($_GET['lang']) AND isset($_GET['service']) AND !empty($_GET['service'])){
	$notification = new Notification();
	switch ($_GET['service']) {
		case telegram:
			$status = $notification->sendTelegram($_GET['token'],$_GET['chatID'],$notification->getMessage($_GET['msg'],$_GET['lang'] ?? "en",$_GET['ch'] ?? ""));
			$notification->sendTelegram($_GET['token'],$_GET['chatID'],"Lieber WLANThermo User, die Software auf deinem Thermometer ist leider veraltet, wodurch einige Schnittstellen, wie die Benachrichtigung, in Zukunft nicht mehr funktionieren werden. Führe bitte ein Update der Firmware durch oder kontaktiere uns über das WLANThermo Forum. Vielen Dank!");
			break;
		case pushover:
			$status = $notification->sendPushover($_GET['token'],$_GET['chatID'],$notification->getMessage($_GET['msg'],$_GET['lang'] ?? "en",$_GET['ch'] ?? ""));	
			$notification->sendPushover($_GET['token'],$_GET['chatID'],"Lieber WLANThermo User, die Software auf deinem Thermometer ist leider veraltet, wodurch einige Schnittstellen, wie die Benachrichtigung, in Zukunft nicht mehr funktionieren werden. Führe bitte ein Update der Firmware durch oder kontaktiere uns über das WLANThermo Forum. Vielen Dank!");
			break;
	}
	if($status){
		SimpleLogger::debug("Message send\n");
		SimpleLogger::debug("Serial:".$_GET['serial']." Token:".$_GET['token']." ChatID:".$_GET['chatID']." IP Adress:".$ip_address."\n");
	}else{
		SimpleLogger::debug("Message not send\n");
		SimpleLogger::debug("Serial:".$_GET['serial']." Token:".$_GET['token']." ChatID:".$_GET['chatID']." IP Adress:".$ip_address."\n");
	}
}else{
    http_response_code(400); // Bad request
	SimpleLogger::error("parameter invalide\n");
	SimpleLogger::dump($_GET . "\n");
	exit;
}