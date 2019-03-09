<?php
/* @author Florian Riedl */
error_reporting(E_ALL);

// getTelegramChatID.php?token=344407734:AAGEdm9gxoFDfuXKUL6HynxDopYrdIYkMPc
if (isset($_GET['token']) AND !empty($_GET['token'])){
	$result = getChatID($_GET['token']);
	if (isset($_GET['callback']) AND !empty($_GET['callback'])){	
		if (!empty($result)){
			echo "".$_GET['callback']."(" . $result . ");";
		}else{
			echo "".$_GET['callback']."(false);";
		}
	}else{
		if (!empty($result)){
			echo $result;
		}else{
			echo 'false';
		}		
	}
}else{
	die('false');
}

function getChatID($token){
	$url = 'https://api.telegram.org/bot' . $token . '/getUpdates';
	$result = json_decode(file_get_contents($url), true);
	return $result['result'][0]['message']['chat']['id'];
}
?>