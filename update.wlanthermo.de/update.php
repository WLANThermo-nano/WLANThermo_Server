<?php
	error_reporting(E_ALL);
		$githubTags = 'https://api.github.com/repos/WLANThermo-nano/WLANThermo_nano_Software/tags';
		$githubLatestRelease = 'https://api.github.com/repos/WLANThermo-nano/WLANThermo_nano_Software/releases/latest';
		$githubReleaseByTagName = 'https://api.github.com/repos/WLANThermo-nano/WLANThermo_nano_Software/releases/tags/';
		$whitelist = array("82e3d4", "82e0b3", "82e66b", "80e78e", "82e95f", "82e83f"); 
		// ("82e3d4-->PhanGreen", "82e0b3-->Steffen", "82e66b-->Alex", "80e78e-->PhanRed", "82e95f-->Armin", "82e83f-->Ha-Ma")
		getcurrentTag();
		
		if (isset($_GET['hardware']) AND isset($_GET['software']) AND isset($_GET['serial']))
        {
			//echo 'GET Parameter übergeben';
			$hardware = $_GET["hardware"];
			$software = $_GET["software"];
			$serial = $_GET["serial"];
			
			if (isset($_GET['checkUpdate']) AND $_GET['checkUpdate'] == 'true'){
				//echo 'checkUpdate';
				if (in_array($serial, $whitelist)) {
					$githubRelease = $githubReleaseByTagName . getcurrentTag();
					//echo $githubRelease;
					$json = getGithubJson($githubRelease);
					if (version_compare($json->tag_name, $software, ">")) {
						echo $json->tag_name;
					}else{
						echo "false";
						//echo $json->tag_name;
					}
				}else{
					$json = getGithubJson($githubLatestRelease);
					if (version_compare($json->tag_name, $software, ">")) {
						echo $json->tag_name;
					}else{
						echo "false";
					}				
				}
				
			}elseif (isset($_GET['getcurrentFirmware'])){
				//echo 'GetCurrentFirmware';
				if (in_array($serial, $whitelist)) {
					//getcurrentFirmwareBeta();
					getFirmware(getGithubwhitelistAPI(), '0', 'Firmware.bin');
					//getFirmwarePhan('http://nano.wlanthermo.de/Nemesis.ino.bin', '0', 'Firmware.bin');
				}else{
					//getcurrentFirmware();
					getFirmware($githubLatestRelease, '0', 'Firmware.bin');
				}
			}elseif (isset($_GET['getcurrentSpiffs'])){
				//echo 'GetCurrentSpiffs';
				if (in_array($serial, $whitelist)) {
					//getcurrentSpiffsBeta();
					getFirmware(getGithubwhitelistAPI(), '1', 'Spiffs.bin');
					//getFirmwarePhan('http://nano.wlanthermo.de/Nemesis.spiffs.bin', '1', 'Spiffs.bin');
				}else{
					getFirmware($githubLatestRelease, '1', 'Spiffs.bin');
					//getcurrentSpiffs();
				}
			}else{
				echo 'false';
				exit();
				//echo 'Nichts zu tun';
			}
		   
        }
		else
		{
			echo 'false';
			//echo 'GET Parameter nicht übergeben';
			exit();
			//abort();
		}
	
	function getGithubJson($githubApiUrl){
		$passwd = 'MyNano85';
		$usernamearray = array("nanoUpdate");
		$usernamekey = array_rand($usernamearray, 1);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $githubApiUrl);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, "$usernamearray[$usernamekey]:$passwd");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: ESP8266'));
		$result = curl_exec($ch);
		return json_decode($result);
		curl_close($ch);
	}
	
	function getGithubwhitelistAPI(){
		global $githubReleaseByTagName;
		$githubRelease = $githubReleaseByTagName . getcurrentTag();
		return $githubRelease;
	}
	
	function getFirmware($githubAPI, $file, $filename){		
		//global $githubReleaseByTagName;
		//$githubRelease = $githubReleaseByTagName . getcurrentTag();
		$json = getGithubJson($githubAPI);
		$binPath = $json->assets[$file]->browser_download_url;
        // the file you want to send
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $binPath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $out = curl_exec($ch);
        curl_close($ch);
        // Set header for binary
        header('Content-type: application/octet-stream');
        header('Content-disposition: attachment; filename="'.$filename.'"');
        header('Content-Transfer-Encoding: binary');
        header("Content-Length: ".strlen($out));
        echo $out;		
	}	

	function getFirmwarePhan($binPath, $file, $filename){		
		//global $githubReleaseByTagName;
		//$githubRelease = $githubReleaseByTagName . getcurrentTag();
        // the file you want to send
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $binPath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $out = curl_exec($ch);
        curl_close($ch);
        // Set header for binary
        header('Content-type: application/octet-stream');
        header('Content-disposition: attachment; filename="'.$filename.'"');
        header('Content-Transfer-Encoding: binary');
        header("Content-Length: ".strlen($out));
        echo $out;		
	}
	
	function getcurrentTag(){
		global $githubTags;
		$json = getGithubJson($githubTags);
		//var_dump($json[0]->name);
		$return = $json[0]->name;
		return $return;
	}
	
	
	function abort(){
		header($_SERVER["SERVER_PROTOCOL"].' 304 Not Modified', true, 304);
	}
	
	function writeLogFile(){
		$format = "csv"; //Moeglichkeiten: csv und txt
 
		$datum_zeit = date("d.m.Y H:i:s");
		$ip = $_SERVER["REMOTE_ADDR"];
		$site = $_SERVER['REQUEST_URI'];
		$browser = $_SERVER["HTTP_USER_AGENT"];
		 
		$monate = array(1=>"Januar", 2=>"Februar", 3=>"Maerz", 4=>"April", 5=>"Mai", 6=>"Juni", 7=>"Juli", 8=>"August", 9=>"September", 10=>"Oktober", 11=>"November", 12=>"Dezember");
		$monat = date("n");
		$jahr = date("y");
		 
		$dateiname="log_".$monate[$monat]."_$jahr.$format";
		 
		$header = array("Datum", "IP", "Seite", "Browser");
		$infos = array($datum_zeit, $ip, $site, $browser);
		 
		if($format == "csv") {
		 $eintrag= '"'.implode('", "', $infos).'"';
		} else { 
		 $eintrag = implode("\t", $infos);
		}
		 
		$write_header = !file_exists($dateiname);
		 
		$datei=fopen($dateiname,"a");
		 
		if($write_header) {
		 if($format == "csv") {
		 $header_line = '"'.implode('", "', $header).'"';
		 } else {
		 $header_line = implode("\t", $header);
		 }
		 
		 fputs($datei, $header_line."\n");
		}
		 
		fputs($datei,$eintrag."\n");
		fclose($datei);	
	}
	writeLogFile();
    exit();
?>