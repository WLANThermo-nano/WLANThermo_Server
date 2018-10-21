<?php
error_reporting(E_ALL);
//$empfaenger_email = 's.ochs.mail@web.de'; 
$empfaenger_email = 'flo.riedl@gmx.at'; 
$betreff = 'Hallo Steffen';
$nachricht = 'Das ist eine Testmail welche am Server generiert wurde!';

$email_header = 'From: "WLANThermo Team" <info@wlanthermo.de>' . "\r\n" .
				'Reply-To: info@wlanthermo.de' . "\r\n" .
				'X-Mailer: PHP/' . phpversion();

mail($empfaenger_email, $betreff, $nachricht, $email_header);

?>