<?php 
 /*************************************************** 
    Copyright (C) 2021  Florian Riedl
    ***************************
		@author Florian Riedl
		@version 1.0, 05/01/21
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
 
 class Notification{
	public function sendTelegram($token,$chat_id,$message){
		$url = 'https://api.telegram.org/bot' . $token . '/sendMessage?text="' . $message . '"&chat_id=' . $chat_id;
		$result = json_decode(file_get_contents($url));
		if($result->ok === true){
			return true;
		}else{
			return false;		
		}		
	}
	
	public function sendPushover($token,$user_key,$message,$priority="0",$retry="30",$expire="300"){
		curl_setopt_array($ch = curl_init(), array(
		  CURLOPT_URL => "https://api.pushover.net/1/messages.json",
		  CURLOPT_POSTFIELDS => array(
			"token" => $token,
			"user" => $user_key,
			"message" => $message,
			"priority" => $priority,
			"retry" => $retry,
			"expire" => $expire,
		  ),
		  CURLOPT_SAFE_UPLOAD => true,
		  CURLOPT_RETURNTRANSFER => true,
		));
		curl_exec($ch);
		curl_close($ch);		
	}
	
	public function sendFirebaseNotification($firebase_server_key,$token,$message=""){
		$data = [
			"notification" => [
				"sound" => "default",
				"body"  => $message,
				"title" => "WLANThermo",
				"content_available" => true,
				"priority" => "high"
			],
			"data" => [
				"sound" => "default",
				"body"  => $message,
				"title" => "WLANThermo",
				"content_available" => true,
				"priority" => "high"
				],
			"to" => $token
		];

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_POST, 1);

		$headers = array();
		$headers[] = 'Content-Type: application/json';
		$headers[] = 'Authorization: key='.$firebase_server_key.'';
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$result = curl_exec($ch);
		curl_close ($ch);
	}
	
	public function getMessage($type,$lang="en",$channel="",$temp="",$limit="",$unit="C"){
		$translation = '{
					"de":{
						"upperLimit":"ACHTUNG! Kanal %s: Temperatur (%s°%s) ist zu hoch (%s°%s)",
						"lowerLimit":"ACHTUNG! Kanal %s: Temperatur (%s°%s) ist zu tief (%s°%s)",
						"battery":"Achtung: Die Batterieladung ist niedrig! Bitte ein Netzteil anschließen.",
						"test":"Testnachricht erfolgreich gesendet. Deine Einstellungen sind korrekt."
					},
					"en":{
						"upperLimit":"ATTENTION! Channel %s: Temperature (%s°%s) is too high (%s°%s)",
						"lowerLimit":"ATTENTION!  Channel %s: Temperature (%s°%s) is too low (%s°%s)",
						"battery":"Attention: Battery charge is low! Please connect a power adapter.",
						"test":"Message sent successfully. Your settings are correct."
					}
		}';
		
		$JsonArr = json_decode( $translation, true );
		if(!array_key_exists($lang,$JsonArr)){
			$lang = 'en';
		}
		
		switch ($type){			
			case 'test':
			case '0':
				return $JsonArr[$lang]['test'];
				break;
				
			case 'lowerLimit':
			case 'down':
			case '1':
				return sprintf($JsonArr[$lang]['lowerLimit'], $channel,$temp,$unit,$limit,$unit);
				break;
				
			case 'upperLimit':
			case 'up':
			case '2':
				return sprintf($JsonArr[$lang]['upperLimit'], $channel,$temp,$unit,$limit,$unit);
				break;
			
			case 'battery':
			case '3':	
				return $JsonArr[$lang]['battery'];
				break;
				
			default:
				return false;
				break;
		}		
	}
 }
 ?>