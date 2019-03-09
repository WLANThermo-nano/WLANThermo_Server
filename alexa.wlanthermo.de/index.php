<?php
error_reporting(E_ALL);
$logfile = '_alexa.log'; // global var for logger class filename
$logpath = '../logs/';  // global var for logger class filepath
require_once("../include/logger.php");
require_once("../config.inc.php");
ob_start('ob_gzhandler');
/* @author Florian Riedl
 */	

// Datenbankverbindung aufbauen --------------------------------------------------------- 

try {
	SimpleLogger::info("############################################################\n");
	SimpleLogger::info("Connecting to the database...\n");
	$dbh = new PDO(sprintf('mysql:host=%s;dbname=%s', $db_server, $db_name), $db_user, $db_pass);
	$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
	$dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
} catch (PDOException $e) {
	SimpleLogger::error("An error has occurred\n");
	SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
	die('false');
}
	
// Übergebenes JSON einlesen ------------------------------------------------------------	
$json = json_decode( file_get_contents('php://input'), true );
// Übergebenes JSON Loggen --------------------------------------------------------------
SimpleLogger::debug("".file_get_contents('php://input')."\n");
// ApplicatonsID auslesen ---------------------------------------------------------------
$amazon_token = $json['session']['application']['applicationId'];
if(checkToken($dbh,$amazon_token)){
	SimpleLogger::debug("Token gefunden\n");
	$responseArray = [
		'version' => '1.0',
			'response' => [
				'outputSpeech' => [
					'type' => 'PlainText',
					'text' => '',
					'ssml' => null
				],
				'shouldEndSession' => true
			]
		];
		$data = getData($dbh,$amazon_token);
		$arr = json_decode( $data, true );

		switch ($json['request']['intent']['name']) {
			case 'nanoAll':
				if ($arr['channel'][0]['temp'] == 999){
					$responseArray['response']['outputSpeech']['text'] = 'Aktuell ist auf Kanal 1 kein Fühler angeschlossen';
				}else if($arr['channel'][0]['temp'] < 5){
					$responseArray['response']['outputSpeech']['text'] = 'Kanal 1 ist arsch kalt.';
				}else{
					$responseArray['response']['outputSpeech']['text'] = 'Deine Temperatur auf Kanal 1 beträgt '.str_replace(".", ",",$arr['channel'][0]['temp']).' Grad.';
				}   
				break;
			case 'nanoCH':
				if($arr['channel'][$json['request']['intent']['slots']['number']['value'] - 1]['temp'] == 999){
					$responseArray['response']['outputSpeech']['text'] = 'Aktuell ist auf Kanal '.$json['request']['intent']['slots']['number']['value'].' kein Fühler angeschlossen.';
				}else{
					$responseArray['response']['outputSpeech']['text'] = 'Deine Temperatur auf Kanal '.$json['request']['intent']['slots']['number']['value'].' beträgt '.str_replace('.', ',',$arr['channel'][$json['request']['intent']['slots']['number']['value'] - 1]['temp']).' Grad.';
				}
				//if($json['request']['intent']['slots']['number']['value']
				break;
			case 'nanoBattery':
				$responseArray['response']['outputSpeech']['text'] = 'Der Akkustand beträgt '.$arr['system']['soc'].' %';
				break;
			case 'nanoCHname':
				$responseArray['response']['outputSpeech']['text'] = 'Dieser Dienst ist nich verfügbar';
				break;
			default:
				if ($arr['channel'][0]['temp'] == 999){
					$responseArray['response']['outputSpeech']['text'] = 'Aktuell ist auf Kanal 1 kein Fühler angeschlossen';
				}else if($arr['channel'][0]['temp'] < 5){
					$responseArray['response']['outputSpeech']['text'] = 'Kanal 1 ist arsch kalt.';
				}else{
					$responseArray['response']['outputSpeech']['text'] = 'Deine Temperatur auf Kanal 1 beträgt '.str_replace(".", ",",$arr['channel'][0]['temp']).' Grad.';
				}
				break;
		}	
		
}else{
	SimpleLogger::debug("Token nicht gefunden\n");
	$responseArray = activate_skill($json['session']['application']['applicationId']);
}
			
// $responseArray = [
            // 'version' => '1.0',
            // 'response' => [
                  // 'outputSpeech' => [
                        // 'type' => 'PlainText',
                        // 'text' => 'Hallo, ich bin Alexa und dein Griller gehört jetzt mir.',
                        // 'ssml' => null
                  // ],
                  // 'shouldEndSession' => true
            // ]
		// ];

header ( 'Content-Type: application/json' );
echo json_encode ( $responseArray );
  
function activate_skill($id){
	return $responseArray = [
		'version' => '1.0',
			'response' => [
				'outputSpeech' => [
					'type' => 'PlainText',
					'text' => 'Hallo, ich bin Alexa und dein Griller gehört jetzt mir.',
					'ssml' => null
				],
				'card' => [
					'type' => 'Simple',
					'title' => 'Aktivierung',
					'content' => 'Bitte lasse deinen Skill aktivieren. Schicke deine ID "'.$id.'" und deine WLANThermo nano Seriennummer an die Entwickler.'
				],
				'shouldEndSession' => true
			]
		];
}	
 
function getData($dbh,$amazon_token){
	try {
		$sql = "SELECT t2.data FROM `devices` as t1, `cloud` as t2 WHERE  t1.serial = t2.serial and t1.amazon_token = :amazon_token ORDER BY t2.id DESC LIMIT 1";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':amazon_token', $amazon_token);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		if ($statement->rowCount() > 0) {
		  return($statement->fetch()['data']);
		} else {
		  return false;
		}
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (getData)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die('false');
	}
} 

function checkToken($dbh,$amazon_token){
	try {
		$sql = "select * from devices where amazon_token= :amazon_token order by id desc limit 1";
		// $sql = "SELECT t2.data FROM `devices` as t1, `cloud` as t2 WHERE  t1.serial = t2.serial and t1.amazon_token = :amazon_token ORDER BY t2.id DESC LIMIT 1";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':amazon_token', $amazon_token);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		if ($statement->rowCount() > 0) {
		  return true;
		} else {
		  return false;
		}
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (checkToken)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die('false');
	}
} 
?>

