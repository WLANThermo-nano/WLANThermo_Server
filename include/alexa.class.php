<?php
function checkAlexaJson($JsonArr){
	if (isset($JsonArr['alexa']['task']) AND !empty($JsonArr['alexa']['task'])){
		return true;
	}else{
		return false;
	}
}

function createAlexaJson($dbh,$JsonArr){
	if(checkAlexaJson($JsonArr)){
		switch ($JsonArr['alexa']['task']) {
			
			case 'save':
				if (insertAlexaKey($dbh,$JsonArr)){
					$JsonArr['alexa']['task'] = 'true';
				}else{
					$JsonArr['alexa']['task'] = 'false';
				}
				break;
			
			case 'delete':
				$JsonArr['alexa']['token'] = NULL;
				if (insertAlexaKey($dbh,$JsonArr)){
					$JsonArr['alexa']['task'] = 'true';
				}else{
					$JsonArr['alexa']['task'] = 'false';
				}
				unset($JsonArr['alexa']['token']);
				break;				
		}
	}else{
		$JsonArr['alexa']['task'] = 'false';	
		SimpleLogger::debug("Json false - ".json_encode($JsonArr['alexa'], JSON_UNESCAPED_SLASHES)."(createAlexaJson)\n");
	}
	return $JsonArr;	
}

function insertAlexaKey($dbh,$JsonArr){
	try {			
		$sql = "UPDATE `devices` 
				SET `amazon_token` = :token 
				WHERE `serial` = :serial";
		$statement = $dbh->prepare($sql);
		$statement->bindValue(':serial', $JsonArr['device']['serial']);
		$statement->bindValue(':token', $JsonArr['alexa']['token']);
		$statement->execute();			
		return true;
	} catch (PDOException $e) {
		SimpleLogger::error("An error has occurred - (insertAlexaKey)\n");
		SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
		return false;
	}
}
?>