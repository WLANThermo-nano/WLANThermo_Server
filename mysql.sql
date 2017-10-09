-- phpMyAdmin SQL Dump
-- version 4.0.10.15
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 09. Okt 2017 um 09:52
-- Server Version: 5.1.73-log
-- PHP-Version: 5.4.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `nano_update`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cloud`
--

CREATE TABLE IF NOT EXISTS `cloud` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `serial` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `api_token` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `data` text COLLATE utf8_unicode_ci NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=459311 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `devices`
--

CREATE TABLE IF NOT EXISTS `devices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `device` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `serial` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `hardware_version` varchar(6) COLLATE utf8_unicode_ci NOT NULL,
  `software_version` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `update_active` tinyint(1) NOT NULL,
  `whitelist` tinyint(1) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `serial` (`serial`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=134 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sw_versions`
--

CREATE TABLE IF NOT EXISTS `sw_versions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `software_version` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `software_id` bigint(20) NOT NULL,
  `prerelease` int(1) NOT NULL,
  `firmware_url` text COLLATE utf8_unicode_ci NOT NULL,
  `spiffs_url` text COLLATE utf8_unicode_ci NOT NULL,
  `firmware_bin` longblob NOT NULL,
  `spiffs_bin` longblob NOT NULL,
  `ts_insert` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `software_version` (`software_version`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=49 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
