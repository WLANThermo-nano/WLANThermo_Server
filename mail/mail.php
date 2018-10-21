<?php
error_reporting(E_ALL);

require_once('class.phpmailer.php');
$mail = new PHPMailer();
$mail->IsSMTP();
$mail->SMTPAuth = true;
$mail->Host = "smtp.strato.de";
$mail->Port = 465;
$mail->Username = "info@wlanthermo.de";
$mail->Password = "test12345";

$mail->SetFrom('florian.riedl@asak.at', 'Web App');
$mail->Subject = "A Transactional Email From Web App";
$mail->MsgHTML($body);
$mail->AddAddress($address, $name);

if($mail->Send()) {
  echo "Message sent!";
} else {
  echo "Mailer Error: " . $mail->ErrorInfo;
}
?>