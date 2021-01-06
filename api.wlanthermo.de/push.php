<?php
		require("../config.inc.php");
		
		$title = '';
		$body = '';
		
		$data = [
			"notification" => [
				"sound" => "default",
				"body"  => "Test Body",
				"title" => "Test Title von Florian",
				"content_available" => true,
				"priority" => "high"
			],
			"data" => [
				"sound" => "default",
				"body"  => "Test Body",
				"title" => "Test Title von Florian",
				"content_available" => true,
				"priority" => "high"
				],
			"to" => "fyty4gEQS8-FLlqHJuor6l:APA91bHfEy3SyiY2koX3Twt84NyRBb5Oat-rEJtC3F6xpUePgdJ-ssRDUXpOoL4GYixl5vUFcI3SOLsIw2n-6OWPx1mR6JE8chQdo21Nsk5Bvby_J9Y6i2-yXxcIjr3BfgxDfNRJEI03"
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
		echo $result;
?>