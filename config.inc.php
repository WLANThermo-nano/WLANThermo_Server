<?php
/* INCLUDE: Verbindung zur Text-Datenbank herstellen */
 
$db_server = getenv("DB_SERVER"); // Hostname
$db_user = getenv("DB_USER"); // Benutzername
$db_pass = getenv("DB_PASS"); // Kennwort
$db_name = getenv("DB_NAME"); // Name der Datenbank


/* TOKEN für Telegram BOT */

$telegram_bot_api = "<YOUR_TELEGRAM_BOT_API_KEY>"; // Telegram API

/* Git WebHook secret */

$hookSecret = array("<YOUR_WEBHOOK_SECRET>");

/* Firebase secret */
$firebase_server_key = "<YOUR_FIREBASE_SERVER_KEY>"; // Server Key for PushNotification
?>