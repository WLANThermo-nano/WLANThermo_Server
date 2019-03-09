<?php
 /*************************************************** 
    Copyright (C) 2018  Florian Riedl
    ***************************
		@author Florian Riedl
		@version 0.2, 30/04/18
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
$logfile = '_message.log'; // global var for logger class filename
$logpath = '../logs/';  // global var for logger class filepath
require_once("../include/logger.php"); // logger class
//-----------------------------------------------------------------------------

// message.php?serial=54gfdgf&token=344407734:AAGEdm9gxoFDfuXKUL6HynxDopYrdIYkMPc&chatID=399660681&ch=0&msg=up&lang=de&service=telegram
// message.php?serial=54gfdgf&token=am7hrgkizhxbz89411y17kzo5w59ii&chatID=uxg4kym3shnhwjnz3ft5min2cw4osp&ch=0&msg=up&lang=de&service=pushover

if (isset($_GET['serial']) AND !empty($_GET['serial']) AND isset($_GET['token']) AND !empty($_GET['token']) AND isset($_GET['chatID']) AND !empty($_GET['chatID']) AND isset($_GET['ch']) AND isset($_GET['msg']) AND !empty($_GET['msg']) AND isset($_GET['lang']) AND !empty($_GET['lang']) AND isset($_GET['service']) AND !empty($_GET['service'])){
	switch ($_GET['service']) {
    case telegram:
		sendTelegram($_GET['token'],$_GET['chatID'],getMsg($_GET['ch'],$_GET['msg'],$_GET['lang']));
        break;
    case pushover:
		sendPushover($_GET['token'],$_GET['chatID'],getMsg($_GET['ch'],$_GET['msg'],$_GET['lang']));
        break;
	}
}else{
	die('false');
}

function getMsg($ch,$msg,$lang){
$de = array("msg0" => "ACHTUNG!","msg1" => "Kanal","msg2" => "hat","up" => "Übertemperatur","down" => "Untertemperatur");
$en = array("msg0" => "ATTENTION!","msg1" => "Channel","msg2" => "has","up" => "overtemperature","down" => "undertemperature");
	
	switch ($lang) {
    case de:
		$message = ''.$de["msg0"].' '.$de["msg1"].''.$ch.' '.$de["msg2"].' '.$de["".$msg.""].'.';
        return $message;
        break;
    case en:
		$message = ''.$en["msg0"].' '.$en["msg1"].''.$ch.' '.$en["msg2"].' '.$en["".$msg.""].'.';
        return $message;
        break;
    default:
		$message = ''.$en["msg0"].' '.$en["msg1"].''.$ch.' '.$en["msg2"].' '.$en["".$msg.""].'.';
		return $message;
	}
}

function sendTelegram($token,$chatID,$msg){
	$url = 'https://api.telegram.org/bot' . $token . '/sendMessage?text="'.$msg.'"&chat_id='.$chatID.'';
	$result = json_decode(file_get_contents($url));
	//var_dump($result);
	if($result->ok === true){
		SimpleLogger::info("Message has been sent! (pushover) - device(".$_GET['serial'].") \n");
	}else{
		SimpleLogger::error("Message could not be sent! (pushover) - device(".$_GET['serial'].") \n");
		SimpleLogger::debug(json_encode($result) . "\n");		
	}
}

function sendPushover($token,$chatID,$msg){
	curl_setopt_array($ch = curl_init(), array(
	  CURLOPT_URL => "https://api.pushover.net/1/messages.json",
	  CURLOPT_POSTFIELDS => array(
		"token" => $token,
		"user" => $chatID,
		"message" => $msg,
	  ),
	  CURLOPT_SAFE_UPLOAD => true,
	  CURLOPT_RETURNTRANSFER => true,
	));
	$result = curl_exec($ch);
	$json_result = json_decode( $result, true );
	if($json_result['status'] == 1){
		SimpleLogger::info("Message has been sent! (pushover) - device(".$_GET['serial'].") \n");
	}else{
		SimpleLogger::error("Message could not be sent! (pushover) - device(".$_GET['serial'].") \n");
		SimpleLogger::debug($result . "\n");
	}
	//{"status":1,"request":"2f095aee-9c80-4c7b-987b-688399bde2d7"}
	//{"token":"invalid","errors":["application token is invalid"],"status":0,"request":"b735dbd0-49ca-4dde-9705-8be42f8e973c"}
	//{"user":"invalid","errors":["user identifier is not a valid user, group, or subscribed user key"],"status":0,"request":"6956bca9-0637-447e-a9ab-d53f18f8c4f0"}
	echo $test;
	curl_close($ch);
}
?>