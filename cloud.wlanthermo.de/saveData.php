<?php
error_reporting(E_ALL);
$logfile = '_saveData.log'; // global var for logger class filename
$logpath = '../logs/';  // global var for logger class filepath
require_once("../include/logger.php");
require_once("../config.inc.php");
/* @author Florian Riedl
 */	
//SimpleLogger::info("############################################################\n");
$json = file_get_contents('php://input');
$arr = array();
$arr = json_decode( $json, true );
//SimpleLogger::info("".$_GET['serial']."\n");
if ($arr === null
    && json_last_error() !== JSON_ERROR_NONE) {
    SimpleLogger::error("JSON invalide ".$json."\n");
	die('false');
}else{
	if (isset($_GET['serial']) AND !empty($_GET['serial']) AND isset($_GET['api_token']) AND !empty($_GET['api_token'])){
		$dbh = connectDatabase($db_server,$db_name,$db_user,$db_pass);
		if($arr['system']['time'] <= '1483228800'){
			$arr['system']['time'] = time();
		}
		$json = json_encode($arr);	
		insertCloud($dbh,$_GET['serial'],$_GET['api_token'],$json);
	}else if (isset($arr['system']['serial']) AND isset($arr['system']['api_token'])){
		$dbh = connectDatabase($db_server,$db_name,$db_user,$db_pass);
		if($arr['system']['time'] <= '1483228800'){
			$arr['system']['time'] = time();
		}
		$serial = $arr['system']['serial'];
		$api_token = $arr['system']['api_token'];
		unset($arr['system']['serial']);
		unset($arr['system']['api_token']);
		$json = json_encode($arr);	
		insertCloud($dbh,$serial,$api_token,$json);	
	}else{
		SimpleLogger::error("Serial or API_Token not set\n");
		SimpleLogger::error("".$json."\n");
		die('false');
	}
	echo "true";
}

function connectDatabase($db_server,$db_name,$db_user,$db_pass){
		try {
			//SimpleLogger::info("Connecting to the database...\n");
			$dbh = new PDO(sprintf('mysql:host=%s;dbname=%s', $db_server, $db_name), $db_user, $db_pass);
			$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
			$dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			return $dbh;
		} catch (PDOException $e) {
			SimpleLogger::error("An error has occurred\n");
			SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
			die('false');
		}	
}
function closeDatabase(){
	$dbh = null;
}

function insertCloud($dbh,$serial,$api_token,$data){
	try {
		$sql = "INSERT INTO `cloud` (`serial`, `api_token`, `data`) VALUES (:serial, :api_token, :data)";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':serial', $serial);
		$statement->bindValue(':api_token', $api_token);
		$statement->bindValue(':data', $data);
		$inserted = $statement->execute();
		if($inserted){
			return true;
		}else{
			return false;
		}
		$statement = null;
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (insertDevice)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die('false');
	}		
}
?>


