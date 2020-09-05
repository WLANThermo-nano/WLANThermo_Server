<?php
 /*************************************************** 
    Copyright (C) 2018  Florian Riedl
    ***************************
		@author Florian Riedl
		@version 0.3, 29/12/18
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
//-----------------------------------------------------------------------------
// error reporting
 error_reporting(E_ALL); 
//-----------------------------------------------------------------------------
// start runtome counter
 $time_start = microtime(true);
//-----------------------------------------------------------------------------
// include Logging libary 
$logfile = '_api.log'; // global var for logger class filename
$logpath = '../logs/';  // global var for logger class filepath
require_once("../include/logger.php"); // logger class
//-----------------------------------------------------------------------------
// include database config
require_once("../dev-config.inc.php"); // 
//-----------------------------------------------------------------------------
$fw_array = array(
        'v0.5.0' => 2,
        'v0.5.3' => 2,
		'v0.6.6' => 9,
        'v0.8.0' => 62,
		'v0.8.3' => 1,
        'v0.8.4' => 3,
		'v0.8.6' => 1,
        'v0.8.7' => 2,
		'v0.8.8' => 3,
        'v0.8.9' => 3,
		'v0.9.0' => 5,
        'v0.9.1' => 1,
		'v0.9.10' => 13,
        'v0.9.11' => 59,
		'v0.9.12' => 52,
        'v0.9.13' => 3,
		'v0.9.4' => 1,
        'v0.9.7' => 1,
		'v0.9.8' => 10,
        'v0.9.9' => 182,
		'v1.0.0-alpha' => 3,
        'v1.0.0-beta' => 3,
		'v1.0.1' => 39,
		'v1.0.2' => 32,
        'v1.0.3' => 31,
		'v1.0.4' => 0,
		'v1.0.5' => 0
    );
?>
<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>WLANThermo Nano SW Version</title>
<style> 
  table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  border: none;
  width:100%;
}
body{
	background-color: #333333;
	color:#FFFFFF; 
}
td, th {
  text-align: left;
  padding: 8px;
}

tr:nth-child(even) {
  background-color: #4a4a4a;
}
</style> 
 </head>
  <body>
	<br>
	<h2>WLANThermo Nano SW Version</h2>
	<table>
	  <tr>
		<th>SW Version</th>
		<th>Anzahl</th>
	  </tr>
	  
<?php
try {
	$dbh = new PDO(sprintf('mysql:host=%s;dbname=%s', $db_server, $db_name), $db_user, $db_pass);
	$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
	$dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
	SimpleLogger::error("Database - Connecting true\n");
} catch (PDOException $e) {
	SimpleLogger::error("Database - An error has occurred\n");
	SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
	die('false');
}

try {
	$sql = "SELECT software_version, COUNT(*) FROM devices where device='nano' GROUP BY software_version order by software_version asc ";
	$statement = $dbh->prepare($sql);
	$statement->execute();
	$statement->setFetchMode(PDO::FETCH_ASSOC);
	if ($statement->rowCount() > 0) {
	  $deviceInfo = $statement->fetchAll();
	 //print_r ($deviceInfo); 
	foreach ($deviceInfo as $row) {
		$diff = $row["COUNT(*)"] - $fw_array[$row["software_version"]];
		if(if_positiv($diff)){
			if($diff > 0){
				$diff_echo = ''.$row["COUNT(*)"].' (<b style="color:green">+'.$diff.'</b>)';
			}else{
				$diff_echo = $row["COUNT(*)"];
			}
		}else{
			$diff_echo = ''.$row["COUNT(*)"].' (<b style="color:red">'.$diff.'</b>)';
		}
		echo '<tr><td>'.$row["software_version"].'</td><td>'.$diff_echo.'</td></tr>';
		//echo ''.$row["software_version"].' '.$row["COUNT(*)"].' <br>';
		//print $row["software_version"] . "-" . $row["COUNT(*)"] ."<br/>";
	}

	} else {
	  echo 'Error';
	}
} catch (PDOException $e) {
	SimpleLogger::error("An error has occurred - (checkNewUpdate)\n");
	SimpleLogger::log(SimpleLogger::DEBUG, $e->getMessage() . "\n");
	//return('false');
}	

function if_positiv($num){
	return $num>=0 ? true : false; 
}
?>
	</table>
  </body>
</html>