<?php
error_reporting(E_ALL);
$logfile = '_getData.log'; // global var for logger class filename
$logpath = '../logs/';  // global var for logger class filepath
require_once("../include/logger.php");
require_once("../config.inc.php");
/* @author Florian Riedl
 */	

//SimpleLogger::info("############################################################\n");
ob_start('ob_gzhandler');

if (isset($_GET['api_token']) AND !empty($_GET['api_token'])){
	if(isset($_GET['time']) AND !empty($_GET['time'])){
		$history_time = $_GET['time'];
	}else{
		$history_time = '24';
	}
	try {
		//SimpleLogger::info("Connecting to the database...\n");
		$dbh = new PDO(sprintf('mysql:host=%s;dbname=%s', $db_server, $db_name), $db_user, $db_pass);
		$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
		$dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die('false');
	}
	if(isset($_GET['chartHistory'])){
		if (isset($_GET['callback']) AND !empty($_GET['callback'])){
			$result = "".$_GET['callback']."('" . getHistory($dbh,$_GET['api_token'],$history_time) . "');";	// Look for Device into Database	
		}else{
			$result = getHistory($dbh,$_GET['api_token'],$history_time); // Look for Device into Database	
		}
		if($result === false){
			die('false');
		}else{
			echo $result;
		}	
	}else if(isset($_GET['chartCSV'])){
		if (isset($_GET['callback']) AND !empty($_GET['callback'])){
			$result = "".$_GET['callback']."('" . getHistory($dbh,$_GET['api_token'],$history_time) . "');";	// Look for Device into Database	
		}else{
			$result = getCSV($dbh,$_GET['api_token'],$history_time); // Look for Device into Database	
		}
		if($result === false){
			die('false');
		}else{
			echo nl2br($result);
		}		
	}else{
		if (isset($_GET['callback']) AND !empty($_GET['callback'])){
			$result = "".$_GET['callback']."(" . getData($dbh,$_GET['api_token']) . ");";
		}else{
			$result = getData($dbh,$_GET['api_token']); // Look for Device into Database				
		}
		if($result === false){
			if (isset($_GET['callback']) AND !empty($_GET['callback'])){
				die("".$_GET['callback']."(false);");
			}else{
				die('false');
			}
			
		}else{
			if (isset($_GET['callback']) AND !empty($_GET['callback'])){
				echo "".$_GET['callback']."(" . $result . ");";
			}else{
				echo $result;
			}
		}
	}
	$dbh = null; //Datenbankverbindung schließen

}else{
	die('false');
}

// ############################################################################################
// Functions ----------------------------------------------------------------------------------
// ############################################################################################

function getData($dbh,$api_token){
	try {
		$sql = "SELECT data FROM `cloud` WHERE api_token= :api_token ORDER BY id DESC LIMIT 1";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':api_token', $api_token);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		if ($statement->rowCount() > 0) {
			$arr = array();
			$obj = json_decode( $statement->fetch()['data'], true );	
			$arr = $obj;
			if(isset($obj['pitmaster'])){	
				if(!isAssoc($obj['pitmaster'])){
					unset($arr['pitmaster']);
					$arr['pitmaster'][0] = $obj['pitmaster'];
				}
			}
			return(json_encode($arr));
		} else {
		  return false;
		}
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (getData)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die('false');
	}
}	
function getHistory($dbh,$api_token,$api_time){
	try {
		$sql = "SELECT data FROM `cloud` WHERE api_token= :api_token AND `time` > TIMESTAMP(DATE_SUB(NOW(), INTERVAL :history_time hour)) order by `id` asc";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':api_token', $api_token);
		$statement->bindValue(':history_time', $api_time);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		$data = array();
		if ($statement->rowCount() > 0) {
			foreach($statement as $daten) {
				$obj = json_decode( $daten['data'], true );
				if ($obj === null && json_last_error() !== JSON_ERROR_NONE) {
					//ToDo Error Hadling
				}else{
					$arr = array(); 
					$arr['system']['time'] = $obj['system']['time'];
					$arr['system']['soc'] = $obj['system']['soc'];
					foreach ( $obj['channel'] as $key => $value )
					{
						$arr['channel'][$key]['temp'] = $value['temp'];
					}
					if(isset($obj['pitmaster'])){					
						if(isAssoc($obj['pitmaster'])){
							foreach ($obj['pitmaster'] as $key => $value)
							{
								$arr['pitmaster'][$key]['value'] = $value['value'];
								$arr['pitmaster'][$key]['set'] = $value['set'];
								$arr['pitmaster'][$key]['typ'] = $value['typ'];
							}					
						}else{
							$arr['pitmaster'][0]['value'] = $obj['pitmaster']['value'];
							$arr['pitmaster'][0]['set'] = $obj['pitmaster']['set'];
							$arr['pitmaster'][0]['typ'] = $obj['pitmaster']['typ'];						
						}
					}
					array_push($data, $arr);
				}
			}
			return(json_encode($data));
		} else {
			return false;
		}
		
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (getHistory)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die('false');
	}
}	

function getCSV($dbh,$api_token,$api_time){
	//echo jsonToCsv(getHistory($dbh,$api_token,$api_time));
	// return jsonToCsv(getHistory($dbh,$api_token,$api_time));
	try {
		$sql = "SELECT data FROM `cloud` WHERE api_token= :api_token AND `time` > TIMESTAMP(DATE_SUB(NOW(), INTERVAL :history_time hour)) order by `id` asc";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':api_token', $api_token);
		$statement->bindValue(':history_time', $api_time);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		$data = array();
		if ($statement->rowCount() > 0) {
			foreach($statement as $daten) {
				$obj = json_decode( $daten['data'], true );
				if ($obj === null && json_last_error() !== JSON_ERROR_NONE) {
					//ToDo Error Hadling
				}else{
					$arr = array(); 
					$arr['system']['time'] = $obj['system']['time'];
					$arr['system']['soc'] = $obj['system']['soc'];
					foreach ( $obj['channel'] as $key => $value )
					{
						$arr['channel'][$key]['temp'] = $value['temp'];
					}
					if(isAssoc($obj['pitmaster'])){
						foreach ($obj['pitmaster'] as $key => $value)
						{
							$arr['pitmaster'][$key]['value'] = $value['value'];
							$arr['pitmaster'][$key]['set'] = $value['set'];
							$arr['pitmaster'][$key]['typ'] = $value['typ'];
						}					
					}else{
						$arr['pitmaster'][0]['value'] = $obj['pitmaster']['value'];
						$arr['pitmaster'][0]['set'] = $obj['pitmaster']['set'];
						$arr['pitmaster'][0]['typ'] = $obj['pitmaster']['typ'];						
					}
					array_push($data, $arr);
				}
			}
			return(jsonToCsv($data));
		} else {
			return false;
		}
		
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (getHistory)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		die('false');
	}
}

function jsonToCsv($json) {
	$csv = '';
	$countchannel = count($json[0]['channel']);
	$countpitmaster = count($json[0]['pitmaster']);
	
	$header = 'Zeit;Batterie';
	for($i=1; $i <= $countchannel; $i++) {
		$header = $header . ';Kanal ' . $i;
	}
	for($i=1; $i <= $countpitmaster; $i++) {
		$header = $header . ';Pit ' . $i . ' Wert; Pit ' . $i . ' Soll; Pit ' . $i . ' Typ';
	}
	
	$header = $header . PHP_EOL;
	$csv .= $header;

	//Write the lines:
	foreach($json as $row){
		$linestring = $row['system']['time'].';'.$row['system']['soc'];
		for($i=0; $i < $countchannel; $i++) {
			$linestring = $linestring . ';' . str_replace('.',',',$row['channel'][$i]['temp']);
		}
		for($i=0; $i < $countpitmaster; $i++) {
			$linestring = $linestring . ';' . $row['pitmaster'][$i]['value'] . ';' . $row['pitmaster'][$i]['set'] . ';' . $row['pitmaster'][$i]['typ'];
		}		
		$csv .= $linestring . PHP_EOL;
	}
	return($csv);
}
  
function isAssoc($arr){
	if (count($arr) == count($arr, COUNT_RECURSIVE)){
		return false;
	}else{
		return true;
	}
}

?>


