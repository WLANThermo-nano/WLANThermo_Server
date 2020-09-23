<?php 
 /*************************************************** 
    Copyright (C) 2020  Florian Riedl
    ***************************
		@author Florian Riedl
		@version 1.1, 23/09/20
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
 
class Cloud extends DB{
	private $token;
	private $serial;
	
	public function insertCloudData($serial,$api_token,$data){
		if(!$this->checkCloudJson($serial,$api_token,$data)){
			return false;
		}
		try {
			$sql = "INSERT INTO `cloud` (`serial`, `api_token`, `data`) 
					VALUES (:serial, :api_token, :data)";
			$statement = $this->connect()->prepare($sql);
			$statement->bindValue(':serial', $serial);
			$statement->bindValue(':api_token', $api_token);
			foreach($data as $key => $d){	
				if(isset($d['pitmaster']) && !$this->isAssoc($d['pitmaster'])){	
					$arr = $d['pitmaster'];
					unset($d['pitmaster']);
					$d['pitmaster'][0] = $arr;
				}

				if($d['system']['time'] <= '1483228800'){
					$d['system']['time'] = time();
				}
				
				$statement->bindValue(':data', json_encode($d, JSON_UNESCAPED_SLASHES));
				$statement->execute();
			}				
			return true;
		} catch (PDOException $e) {
			return false;
		}			
	}
	
	private function checkCloudJson($serial,$api_token,$data){
		if (isset($serial) AND !empty($serial) AND 
			isset($api_token) AND !empty($api_token) AND
			isset($data) AND !empty($data)){
			return true;
		}else{
			return false;
		}
	}
	
	public function readCloudData($api_token,$from = null,$to = null){

		$from = isset($from) ? $from : strtotime("-1 day");
		$to = isset($to) ? $to : strtotime("now");
		
		try {
			$sql = "SELECT data FROM `cloud` WHERE api_token= :api_token AND time >= FROM_UNIXTIME(:from) AND time <= FROM_UNIXTIME(:to) ORDER BY `id` asc";
			$statement = $this->connect()->prepare($sql);
			$statement->bindValue(':api_token', $api_token);
			$statement->bindValue(':from', $from);
			$statement->bindValue(':to', $to);
			$statement->execute();
			$statement->setFetchMode(PDO::FETCH_ASSOC);
			$data = array();
			if ($statement->rowCount() > 0) {
				foreach($statement as $daten) {
					$obj = json_decode( $daten['data'], true );
					if ($obj === null && json_last_error() !== JSON_ERROR_NONE) {
						return false;
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
							foreach ($obj['pitmaster'] as $key => $value)
							{
								$arr['pitmaster'][$key]['value'] = $value['value'];
								$arr['pitmaster'][$key]['set'] = $value['set'];
								$arr['pitmaster'][$key]['typ'] = $value['typ'];
							}					
						}
						array_push($data, $arr);
					}
				}
				return($data);
			} else {
				return false;
			}
			
		} catch (PDOException $e) {
			return false;
		}	
	}
	
	private function isAssoc($arr){
		if (count($arr) == count($arr, COUNT_RECURSIVE)){
			return false;
		}else{
			return true;
		}			
	}
}
?>