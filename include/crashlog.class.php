<?php 
 /*************************************************** 
    Copyright (C) 2021  Florian Riedl
    ***************************
		@author Florian Riedl
		@version 1.0, 09/06/21
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
 
class Crashreport extends DB{

	public function saveReport($serial,$reason,$report){
		try {
			$sql = "INSERT INTO `crashreport` (`serial`, `reason`, `report`) 
					VALUES (:serial, :reason, :report)";
			$statement = $this->connect()->prepare($sql);
			$statement->bindValue(':serial', $serial);
			$statement->bindValue(':reason', $reason);
			$statement->bindValue(':report', $report);
			$statement->execute();			
			return true;
		} catch (PDOException $e) {
			return false;
		}			
	}
}
?>