-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Erstellungszeit: 31. Okt 2023 um 20:55
-- Server-Version: 10.1.44-MariaDB-0ubuntu0.18.04.1
-- PHP-Version: 8.2.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `wlt_cloud`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `alexa`
--

CREATE TABLE `alexa` (
  `id` int(11) UNSIGNED NOT NULL,
  `serial` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `amazon_token` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cloud`
--

CREATE TABLE `cloud` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `serial` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `api_token` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `data` text COLLATE utf8_unicode_ci NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `crashreport`
--

CREATE TABLE `crashreport` (
  `id` int(10) UNSIGNED NOT NULL,
  `serial` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `reason` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `report` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `devices`
--

CREATE TABLE `devices` (
  `id` int(10) UNSIGNED NOT NULL,
  `device` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `serial` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `item` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `amazon_token` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `hardware_version` varchar(6) COLLATE utf8_unicode_ci NOT NULL,
  `software_version` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `cpu` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `flash_size` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `history`
--

CREATE TABLE `history` (
  `id` bigint(20) NOT NULL,
  `serial` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `api_token` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `ts_start` int(11) NOT NULL,
  `ts_stop` int(11) NOT NULL,
  `data` longtext COLLATE utf8_unicode_ci NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `software_files`
--

CREATE TABLE `software_files` (
  `id` int(10) NOT NULL,
  `device` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `hardware_version` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `cpu` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `software_version` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `release_id` bigint(20) NOT NULL,
  `asset_id` bigint(20) NOT NULL,
  `prerelease` int(1) NOT NULL,
  `breakpoint` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `file_type` tinytext NOT NULL,
  `file_name` tinytext NOT NULL,
  `file_url` text NOT NULL,
  `file_sha256` text NOT NULL,
  `file` longblob NOT NULL,
  `ts_insert` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sw_versions`
--

CREATE TABLE `sw_versions` (
  `id` int(10) UNSIGNED NOT NULL,
  `software_version` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `device` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `software_id` bigint(20) NOT NULL,
  `prerelease` int(1) NOT NULL,
  `firmware_url` text COLLATE utf8_unicode_ci NOT NULL,
  `spiffs_url` text COLLATE utf8_unicode_ci NOT NULL,
  `firmware_bin` longblob NOT NULL,
  `spiffs_bin` longblob NOT NULL,
  `ts_insert` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `alexa`
--
ALTER TABLE `alexa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device` (`serial`);

--
-- Indizes für die Tabelle `cloud`
--
ALTER TABLE `cloud`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `crashreport`
--
ALTER TABLE `crashreport`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serial` (`serial`);

--
-- Indizes für die Tabelle `history`
--
ALTER TABLE `history`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `software_files`
--
ALTER TABLE `software_files`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `sw_versions`
--
ALTER TABLE `sw_versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `software_version` (`software_version`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `alexa`
--
ALTER TABLE `alexa`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `cloud`
--
ALTER TABLE `cloud`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `crashreport`
--
ALTER TABLE `crashreport`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `history`
--
ALTER TABLE `history`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `software_files`
--
ALTER TABLE `software_files`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `sw_versions`
--
ALTER TABLE `sw_versions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
