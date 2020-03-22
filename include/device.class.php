<?php 
 /*************************************************** 
    Copyright (C) 2020  Florian Riedl
    ***************************
		@author Florian Riedl
		@version 0.2, 15/03/20
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
 
class Device extends DB{
	private $device;
	private $serial;
	private $hardwareVersion;
	private $softwareVersion;
	private $cpu;
	private $flash_size;
	private $name;
	private $item;
	private $deviceActive;

	public function __construct($device, $serial, $hardwareVersion, $softwareVersion, $cpu = 'esp82xx', $flash_size = '0', $item = null ) {
		
		$this->device = $device;
		$this->serial = $serial;
		$this->hardwareVersion = $hardwareVersion;
		$this->softwareVersion = $softwareVersion;
		$this->cpu = $cpu;
		$this->flash_size = $flash_size;
		$this->item = $item;
		
		// prüfen ob Device in der Datenbank existiert
		$devicefromDB = $this->getDevicefromDB();
		if($devicefromDB){
			$this->deviceActive = $devicefromDB['active'];
			if($this->compareDeviceFromDB($devicefromDB)){
				$this->updateDeviceTSfromDB();
			}else{
				$this->updateDevicefromDB();				
			}
		}else{
			// Device anlegen
			$this->insertNewDevice();
			$this->deviceActive = true;
		}		
	}
	
	private function getDevicefromDB(){
		$sql = "select * FROM devices WHERE serial = :serial";
		$statement = $this->connect()->prepare($sql);
		$statement->bindValue(':serial', $this->serial);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		
		return $statement->fetch();
	}
	
	private function updateDevicefromDB(){
		$sql = "UPDATE devices SET 	
									device = :device,
									hardware_version = :hardware_version,
									software_version = :software_version,
									cpu = :cpu,
									flash_size = :flash_size,
									item = :item
								WHERE 
									serial = :serial";
		$statement = $this->connect()->prepare($sql);
		$statement->bindValue(':device', $this->device);
		$statement->bindValue(':serial', $this->serial);
		$statement->bindValue(':item', $this->item);
		$statement->bindValue(':hardware_version', $this->hardwareVersion);
		$statement->bindValue(':software_version', $this->softwareVersion);
		$statement->bindValue(':cpu', $this->cpu);
		$statement->bindValue(':flash_size', $this->flash_size);
		$inserted = $statement->execute();
		if($inserted){
			return true;
		}else{
			return false;
		}		
	}

	private function updateDeviceTSfromDB(){
		$sql = "UPDATE devices SET timestamp=now() WHERE serial = :serial";
		$statement = $this->connect()->prepare($sql);
		$statement->bindValue(':serial', $this->serial);
		$updated = $statement->execute();
		if($updated){
			return true;
		}else{
			return false;
		}
	} 

	/**
	 * compare device
	 *
	 * @param array
	 * @return bool true/false
	**/
	private function compareDeviceFromDB($devicefromDB){
		if($devicefromDB['device'] == $this->device AND 
				$devicefromDB['item'] == $this->item AND 
				$devicefromDB['hardware_version'] == $this->hardwareVersion AND 
				$devicefromDB['software_version'] == $this->softwareVersion AND 
				$devicefromDB['cpu'] == $this->cpu AND 
				$devicefromDB['flash_size'] == $this->flash_size){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * insert new Device into database
	 *
	 * @return bool true/false
	**/	
	private function insertNewDevice(){					
		$sql = "INSERT INTO devices (device, serial, hardware_version, software_version, cpu, flash_size, item) 
				VALUES (:device, :serial, :hardware_version, :software_version, :cpu, :flash_size, :item)";
		$statement = $this->connect()->prepare($sql);
		$statement->bindValue(':device', $this->device);
		$statement->bindValue(':serial', $this->serial);
		$statement->bindValue(':item', $this->item);
		$statement->bindValue(':hardware_version', $this->hardwareVersion);
		$statement->bindValue(':software_version', $this->softwareVersion);
		$statement->bindValue(':cpu', $this->cpu);
		$statement->bindValue(':flash_size', $this->flash_size);
		$inserted = $statement->execute();
		if($inserted){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * get device
	 *
	 * @return string
	**/	 
	public function getDevice(){
		return $this->device; 
	}

	/**
	 * get serial
	 *
	 * @return string
	**/	 
	public function getSerial(){
		return $this->serial; 
	}

	/**
	 * get hardware version
	 *
	 * @return string
	**/	 
	public function getHardwareVersion(){
		return $this->hardwareVersion; 
	}

	/**
	 * get software version
	 *
	 * @return string
	**/	 
	public function getSoftwareVersion(){
		return $this->softwareVersion; 
	}

	/**
	 * get device status
	 *
	 * @return bool true/false
	**/	
	public function getDeviceActive(){
		if($this->deviceActive){
			return true;
		}else{
			return false;
		}
		//return $this->deviceActive; 
	}

    private function getSoftwareUpdateReleaseId($prerelease){
		if($prerelease){
			$sql = "select software_version, prerelease, breakpoint, release_id, active 
					FROM software_files 
					WHERE device = :device 
					AND hardware_version = :hardware_version AND cpu = :cpu AND active=true 
					GROUP by release_id";			
		}else{
			$sql = "select software_version, prerelease, breakpoint, release_id, active 
					FROM software_files 
					WHERE device = :device 
					AND hardware_version = :hardware_version AND cpu = :cpu AND active=true AND prerelease = false
					GROUP by release_id";				
		}
        $statement = $this->connect()->prepare($sql);
        $statement->bindValue(':device', $this->device);
        $statement->bindValue(':hardware_version', $this->hardwareVersion);
        $statement->bindValue(':cpu', $this->cpu);
        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $output = $statement->fetchAll();
        usort($output, function($a,$b) {
            return -1 * version_compare ( $a['software_version'] , $b['software_version'] );
        });
        $output = array_reverse($output);
        $keys = array_keys($output);
        for($i = 0; $i < count($output); $i++) {
            if(version_compare($output[$keys[$i]]['software_version'],$this->softwareVersion) == 1  AND 
                ($output[$keys[$i]]['breakpoint'] == true OR count($output) -1 == $i))
            {
                return $output[$keys[$i]]['release_id'];
                break;
            }
        }
        return false;
    }
	
	public function getSoftwareUpdate($prerelease = false){
		$release_id = $this->getSoftwareUpdateReleaseId($prerelease);
		if($release_id){
			$sql = "select software_version, asset_id, file_type, file_sha256, file_url 
					FROM software_files 
					WHERE device = :device 
					AND hardware_version = :hardware_version 
					AND cpu = :cpu 
					AND release_id = :release_id
					";
			$statement = $this->connect()->prepare($sql);
			$statement->bindValue(':device', $this->device);
			$statement->bindValue(':hardware_version', $this->hardwareVersion);
			$statement->bindValue(':release_id', $release_id);
			$statement->bindValue(':cpu', $this->cpu);
			$statement->execute();
			$statement->setFetchMode(PDO::FETCH_ASSOC);
			$output = $statement->fetchAll();
			return $output;
		}else{
			return false;
		}
	}
	
	public function getSoftwareByVersion($version, $file_type = null){
		$sql = "select software_version, asset_id, file_type, file_sha256, file_url 
				FROM software_files 
				WHERE device = :device 
				AND hardware_version = :hardware_version 
				AND cpu = :cpu 
				AND software_version = :software_version
				AND active=true
			";		
		$statement = $this->connect()->prepare($sql);
		$statement->bindValue(':device', $this->device);
		$statement->bindValue(':hardware_version', $this->hardwareVersion);
		$statement->bindValue(':software_version', strtolower($version));
		$statement->bindValue(':cpu', $this->cpu);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		if ($statement->rowCount() > 0) {
			return $statement->fetchAll();	
		} else {
			return false;
		}
	}
	public function getSoftwareByFileType($version, $file_type){
		$sql = "select software_version, asset_id, file_type, file_sha256, file_url 
				FROM software_files 
				WHERE device = :device 
				AND hardware_version = :hardware_version 
				AND cpu = :cpu 
				AND file_type = :file_type
				AND software_version = :software_version
				AND active=true
			";		
		$statement = $this->connect()->prepare($sql);
		$statement->bindValue(':device', $this->device);
		$statement->bindValue(':hardware_version', $this->hardwareVersion);
		$statement->bindValue(':software_version', strtolower($version));
		$statement->bindValue(':file_type', $file_type);
		$statement->bindValue(':cpu', $this->cpu);
		$statement->execute();
		$statement->setFetchMode(PDO::FETCH_ASSOC);
		if ($statement->rowCount() > 0) {
			return $statement->fetchAll();	
		} else {
			return false;
		}
	}
}

?>