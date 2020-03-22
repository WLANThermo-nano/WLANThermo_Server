<?php 
 /*************************************************** 
    Copyright (C) 2020  Florian Riedl
    ***************************
		@author Florian Riedl
		@version 1.0, 22/03/20
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
}

?>