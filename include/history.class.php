<?php
function createHistoryJson($dbh,$JsonArr){
	if (isset($JsonArr['history']['task']) AND !empty($JsonArr['history']['task'])){
		switch ($JsonArr['history']['task']) {
			case 'save':
				if (isset($JsonArr['history']['api_token']) AND !empty($JsonArr['history']['api_token'])){			
					if (insertHistoryData($dbh,$JsonArr)){
						$JsonArr['history']['task'] = 'true';
					}else{
						$JsonArr['history']['task'] = 'false';
					}
				}else{
					$JsonArr['history']['task'] = 'false';	
					SimpleLogger::debug("Json false - ".json_encode($JsonArr['history'], JSON_UNESCAPED_SLASHES)."(createHistoryJson)\n");
				}
				break;
			case 'read':
				$tmp = readHistoryData($dbh,$JsonArr);
				if ($tmp == false){
						$JsonArr['history']['task'] = 'false';
					}else{
						$JsonArr['history']['task'] = 'true';
						$JsonArr['history']['list'] = $tmp;
				}
				break;	
			case 'delete':
				$tmp = deleteHistoryData($dbh,$JsonArr);
				if ($tmp == false){
						$JsonArr['history']['task'] = 'false';
					}else{
						$JsonArr['history']['task'] = 'true';
				}
				break;				
		}
	}else{
		$JsonArr['history']['task'] = 'false';	
		SimpleLogger::debug("Json false - ".json_encode($JsonArr['history'], JSON_UNESCAPED_SLASHES)."(createHistoryJson)\n");
	}
	
	return $JsonArr;
}

function readHistoryData($dbh,$JsonArr){	
	try {
		$tmp = array();
		$sql = "SELECT api_token, ts_start, ts_stop FROM `history` WHERE serial= :serial order by `id` desc";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':serial', $JsonArr['device']['serial']);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		if ($statement->rowCount() > 0) {
			foreach($statement as $key => $daten) {
				array_push($tmp, $daten);
			}
			return $tmp;
		}else{
			return false;	
		}
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (readHistoryData)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		return false;
	}
}

function deleteHistoryData($dbh,$JsonArr){	
	try {
		$tmp = array();
		$sql = "DELETE FROM `history` WHERE api_token= :api_token";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':api_token', $JsonArr['history']['api_token']);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		if ($statement->rowCount() > 0) {
			return true;
		}else{
			return false;	
		}
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (readHistoryData)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		return false;
	}
}
		
function insertHistoryData($dbh,$JsonArr){	
	try {
		$api_time = '24';
		$sql = "SELECT data FROM `cloud` WHERE api_token= :api_token AND serial= :serial AND `time` > TIMESTAMP(DATE_SUB(NOW(), INTERVAL :history_time hour)) order by `id` asc";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':api_token', $JsonArr['history']['api_token']);
		$statement->bindValue(':serial', $JsonArr['device']['serial']);
		$statement->bindValue(':history_time', $api_time);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		$tmp = array();
		$data = array();
		SimpleLogger::debug($c);
		if ($statement->rowCount() > 0) {
			$numItems = $statement->rowCount() - 1;
			foreach($statement as $key => $daten) {
				$obj = json_decode( $daten['data'], true );
				if($key == $numItems){
					$data['header']['ts_stop'] = $obj['system']['time'];
					$arr = $obj;
					if(isset($obj['pitmaster'])){	
						if(!isAssoc($obj['pitmaster'])){
							unset($arr['pitmaster']);
							$arr['pitmaster'][0] = $obj['pitmaster'];
						}
					}					
					$data['last_data'] = $arr;
					
				} else {
					if($key == 0){
						$data['header']['ts_start'] = $obj['system']['time'];
					}
					if ($obj === null && json_last_error() !== JSON_ERROR_NONE) {
					}else{
						$arr = array(); 
						$arr['system']['time'] = $obj['system']['time'];
						$arr['system']['soc'] = $obj['system']['soc'];
						foreach ( $obj['channel'] as $key => $value )
						{
							$arr['channel'][$key]['temp'] = $value['temp'];
						}
						// if(isAssoc($obj['pitmaster'])){
							foreach ($obj['pitmaster'] as $key => $value)
							{	
								$arr['pitmaster'][$key]['value'] = $value['value'];
								$arr['pitmaster'][$key]['set'] = $value['set'];
								$arr['pitmaster'][$key]['typ'] = $value['typ'];
							}					
						// }else{
							// $arr['pitmaster'][0]['value'] = $obj['pitmaster']['value'];
							// $arr['pitmaster'][0]['set'] = $obj['pitmaster']['set'];
							// $arr['pitmaster'][0]['typ'] = $obj['pitmaster']['typ'];						
						// }
						array_push($tmp, $arr);
					}						// not last element
				}
			}		
			$data['data'] = $tmp;
			//array_unshift($data, $data['settings']);
			$sql = "INSERT INTO `history` (`api_token`,`serial`,`ts_start`,`ts_stop`,`data`) VALUES (:api_token, :serial, :ts_start, :ts_stop, :data)";
			$statement = $dbh->prepare($sql);			
			$statement->bindValue(':data', json_encode($data, JSON_UNESCAPED_SLASHES));
			$statement->bindValue(':serial', $JsonArr['device']['serial']);
			$statement->bindValue(':api_token', guidv4());
			$statement->bindValue(':ts_start', $data['header']['ts_start']);
			$statement->bindValue(':ts_stop', $data['header']['ts_stop']);
			$statement->execute();
			return true;	
				//return(json_encode($data));
		} else {
			return false;
		}	
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (insertHistoryData)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		return false;
	}
}

?>