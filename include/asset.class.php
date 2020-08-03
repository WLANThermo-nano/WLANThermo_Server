<?php
 /*************************************************** 
    Copyright (C) 2020  Florian Riedl
    ***************************
		@author Florian Riedl
		@version 1.0, 13/02/20
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

class asset extends DB{
	
	private $asset_id;
	private $file_name;
	
	public function getAssetId(){
		return $this->asset_id;
	}
	
	public function setAssetId($id){
		$this->asset_id = $id;
	}
	
	public function getFile(){
		if($this->asset_id){

			$sql = "SELECT file 
					FROM software_files 
					WHERE asset_id=:asset_id";
			$statement = $this->connect()->prepare($sql);
			$statement->bindValue(':asset_id', $this->asset_id);
			$statement->execute();
			$statement->bindColumn(1,$firmware, PDO::PARAM_LOB);
			$statement->fetch(PDO::FETCH_BOUND);
			if(!empty($firmware)){
				return $firmware;
			}else{
				return false;
			}			
		}else{
			return false;
		}
	}	
	
	public function getFileName(){
		$sql = "SELECT file_name 
				FROM software_files 
				WHERE asset_id=:asset_id";
		$statement = $this->connect()->prepare($sql);
		$statement->bindValue(':asset_id', $this->asset_id);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		if ($statement->rowCount() > 0) {
			$return = $statement->fetch();
			return $return['file_name'];	
		} else {
			return false;
		}			
	}
}
?>