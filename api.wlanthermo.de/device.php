<?php 

require_once("../include/db.class.php");
require_once("../include/asset.class.php");

// echo 'Test';
// //$output = new Device("nano", "84b3ecT1", "v1", "v1.0.1", "esp82xx");
// //var_dump('NEU');
// //echo '<pre>' . var_export($output->getSoftwareUpdate(), true) . '</pre>';
// //print_r($output->getUpdateVersion());
// // var_dump($output->hardwareVersion);
// $output = new Update();
// var_dump($output);
// $output->setAssetId = "17877160";
// echo $output->getAssetId;
// $output->getFile();


 
// bisher passiert noch gar nichts,
// jetzt wird aus der Klasse ein Objekt erzeugt
//$asset = new asset;
 
// dem Auto wird nun der Kraftstoff zugewiesen,
// eine Eigenschaft (Attribut) wird definiert

 
// und nun wird das erste mal die Methode (Funktion)
// tankdeckel_oeffnen aufgerufen und das Auto sagt
// freudig, was es für Sprit benötigt
$asset = new asset();
$asset->setAssetId('17877160');
//echo $asset->getAssetId();
$file = $asset->getFile();
if($file){
	// header('Content-type: application/octet-stream');
	// header('Content-disposition: attachment; filename="file.bin"');
	// header('Content-Transfer-Encoding: binary');
	// header("Content-Length: ".strlen($file));
	echo hash('sha256',$file);
	//echo($file);
}else{
	echo "false";
}


//var_dump($file);
?>