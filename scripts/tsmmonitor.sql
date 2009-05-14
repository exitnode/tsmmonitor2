-- phpMyAdmin SQL Dump
-- version 2.11.9.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 14. Mai 2009 um 12:25
-- Server Version: 5.0.45
-- PHP-Version: 5.2.3-1ubuntu6.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `tsmmonitor`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cfg_colors`
--

CREATE TABLE IF NOT EXISTS `cfg_colors` (
  `name` varchar(35) collate utf8_unicode_ci NOT NULL,
  `value` varchar(7) collate utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Daten für Tabelle `cfg_colors`
--

INSERT INTO `cfg_colors` (`name`, `value`) VALUES
('ok_dark', '#57E964'),
('ok_light', '#5FFC6F'),
('alarm_dark', '#F02214'),
('alarm_light', '#FF0000'),
('warn_dark', '#FFCC00'),
('warn_light', '#FFFF00');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cfg_config`
--

CREATE TABLE IF NOT EXISTS `cfg_config` (
  `id` int(11) NOT NULL auto_increment,
  `confkey` varchar(35) collate utf8_unicode_ci NOT NULL,
  `confval` varchar(35) collate utf8_unicode_ci NOT NULL,
  `description` varchar(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=3 ;

--
-- Daten für Tabelle `cfg_config`
--

INSERT INTO `cfg_config` (`id`, `confkey`, `confval`, `description`) VALUES
(1, 'timeout', '200', 'change this value to your desired php timeout'),
(2, 'version', '0.1.0', '');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cfg_groups`
--

CREATE TABLE IF NOT EXISTS `cfg_groups` (
  `id` int(11) NOT NULL auto_increment,
  `group` varchar(35) collate utf8_unicode_ci NOT NULL default 'users',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

--
-- Daten für Tabelle `cfg_groups`
--

INSERT INTO `cfg_groups` (`id`, `group`) VALUES
(1, 'testgroup');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cfg_mainmenu`
--

CREATE TABLE IF NOT EXISTS `cfg_mainmenu` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(35) collate utf8_unicode_ci NOT NULL,
  `label` varchar(35) collate utf8_unicode_ci NOT NULL,
  `sortorder` smallint(6) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=8 ;

--
-- Daten für Tabelle `cfg_mainmenu`
--

INSERT INTO `cfg_mainmenu` (`id`, `name`, `label`, `sortorder`) VALUES
(1, 'nodes', 'Nodes', 10),
(2, 'server', 'Server', 20),
(3, 'backup', 'Backup / Archive', 30),
(4, 'schedules', 'Schedules', 40),
(5, 'activity', 'Activity', 50),
(6, 'serverstorage', 'Server Storage', 60),
(7, 'offsite', 'Offsite Management', 70);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cfg_overviewboxes`
--

CREATE TABLE IF NOT EXISTS `cfg_overviewboxes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(35) collate utf8_unicode_ci NOT NULL,
  `label` varchar(35) collate utf8_unicode_ci NOT NULL,
  `sortorder` tinyint(4) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5 ;

--
-- Daten für Tabelle `cfg_overviewboxes`
--

INSERT INTO `cfg_overviewboxes` (`id`, `name`, `label`, `sortorder`) VALUES
(1, 'healthdata', 'Health Status', 10),
(2, 'database', 'TSM Database', 20),
(3, 'totaldata', 'Total Data', 30),
(4, 'schedules', 'Schedule Status', 40);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cfg_overviewqueries`
--

CREATE TABLE IF NOT EXISTS `cfg_overviewqueries` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(35) collate utf8_unicode_ci NOT NULL,
  `query` varchar(255) collate utf8_unicode_ci NOT NULL,
  `unit` varchar(10) collate utf8_unicode_ci NOT NULL,
  `alert_comp` varchar(35) collate utf8_unicode_ci NOT NULL,
  `alert_val` varchar(35) collate utf8_unicode_ci NOT NULL,
  `alert_col` varchar(35) collate utf8_unicode_ci NOT NULL,
  `notforlibclient` int(1) NOT NULL default '0',
  `pollfreq` int(11) NOT NULL,
  `parent` varchar(35) collate utf8_unicode_ci NOT NULL,
  `sortorder` tinyint(4) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=20 ;

--
-- Daten für Tabelle `cfg_overviewqueries`
--

INSERT INTO `cfg_overviewqueries` (`id`, `name`, `query`, `unit`, `alert_comp`, `alert_val`, `alert_col`, `notforlibclient`, `pollfreq`, `parent`, `sortorder`) VALUES
(1, 'Total Data stored', 'SELECT CAST(FLOAT(SUM(logical_mb)) / 1024 / 1024 AS DEC(8,3)) FROM occupancy', 'TB', '', '', '', 0, 3, 'totaldata', 10),
(2, 'Total Data backuped', 'SELECT cast(float(sum(bytes))/1024/1024/1024/1024 as dec(8,3)) FROM SUMMARY WHERE ACTIVITY=''BACKUP'' AND (CURRENT_TIMESTAMP-END_TIME)HOURS LESS= 24 HOURS', 'TB', '', '', '', 0, 3, 'totaldata', 20),
(3, 'Total Data archived', 'SELECT cast(float(sum(bytes))/1024/1024/1024/1024 as dec(8,3)) FROM SUMMARY WHERE ACTIVITY=''ARCHIVE'' AND (CURRENT_TIMESTAMP-END_TIME)HOURS LESS= 24 HOURS', 'TB', '', '', '', 0, 3, 'totaldata', 30),
(4, 'Scratch Tapes', 'SELECT COUNT(*) FROM libvolumes WHERE status=''Scratch''', '', 'less', '10', 'alarm', 1, 3, 'healthdata', 10),
(5, 'Volumes with Errors', 'SELECT COUNT(*) FROM volumes WHERE error_state=''YES''', '', 'more', '0', 'alarm', 0, 3, 'healthdata', 20),
(6, 'Unavailable Volumes', 'SELECT COUNT(*) FROM volumes WHERE access=''UNAVAILABLE''', '', 'more', '0', 'alarm', 0, 3, 'healthdata', 30),
(7, 'Drives Offline', 'SELECT COUNT(*) FROM drives WHERE NOT online=''YES''', '', 'more', '0', 'alarm', 1, 3, 'healthdata', 40),
(8, 'Paths Offline', 'SELECT COUNT(*) FROM paths WHERE NOT online=''YES''', '', 'more', '0', 'alarm', 1, 3, 'healthdata', 50),
(9, 'Cache Hit', 'SELECT CACHE_HIT_PCT from db', '%', 'less', '98.0', 'warn', 0, 3, 'database', 10),
(10, 'Database Usage', 'SELECT pct_utilized FROM db', '%', 'more', '90.0', 'alarm', 0, 3, 'database', 20),
(11, 'Log Usage', 'SELECT pct_utilized FROM log', '%', 'more', '90.0', 'alarm', 0, 3, 'database', 30),
(12, 'DB Fragmentation', 'SELECT CAST((100 - (CAST(MAX_REDUCTION_MB AS FLOAT) * 256 ) / (CAST(USABLE_PAGES AS FLOAT) - CAST(USED_PAGES AS FLOAT) ) * 100) AS DECIMAL(4,2)) AS PERCENT_FRAG FROM DB', '%', 'more', '10.0', 'alarm', 0, 3, 'database', 40),
(13, 'DB Volumes not synced', 'SELECT COUNT(*) FROM dbvolumes WHERE ( NOT copy1_status=''Synchronized'' OR NOT copy2_status=''Synchronized'' OR NOT copy3_status=''Synchronized'')', '', 'more', '0', 'alarm', 0, 3, 'database', 50),
(14, 'DB Logs not synced', 'SELECT COUNT(*) FROM logvolumes WHERE ( NOT copy1_status=''Synchronized'' OR NOT copy2_status=''Synchronized'' OR NOT copy3_status=''Synchronized'')', '', 'more', '0', 'alarm', 0, 3, 'database', 60),
(15, 'Last DB Backup', 'SELECT last_backup_date FROM db', '', '', '', '', 0, 3, 'database', 70),
(16, 'Failed Admin Schedules', 'SELECT count(*) FROM events WHERE status NOTEQUAL ''Completed'' AND status NOTEQUAL ''Future'' AND status NOTEQUAL ''Started'' AND status NOTEQUAL ''Restarted'' and status NOTEQUAL ''In Progres'' and status NOTEQUAL ''Pending'' and NODE_NAME is NULL', '', 'more', '0', 'alarm', 0, 3, 'schedules', 10),
(17, 'Failed Client Schedules', 'SELECT count(*) FROM events WHERE status NOTEQUAL ''Completed'' AND status NOTEQUAL ''Future'' AND status NOTEQUAL ''Started'' AND status NOTEQUAL ''Restarted'' and status NOTEQUAL ''In Progres'' and status NOTEQUAL ''Pending'' AND NODE_NAME is NOT NULL', '', 'more', '0', 'alarm', 0, 3, 'schedules', 20),
(18, 'Running Client Schedules', 'SELECT count(*) FROM events WHERE status NOTEQUAL ''Completed'' AND status NOTEQUAL ''Future'' AND (status=''In Progres'' OR status=''Started'' OR status=''Restarted'') and NODE_NAME is NOT NULL', '', 'more', '0', 'warn', 0, 3, 'schedules', 30),
(19, 'Running Admin Schedules', 'SELECT count(*) FROM events WHERE status NOTEQUAL ''Completed'' AND status NOTEQUAL ''Future'' AND (status=''In Progre%'' OR status=''Started'' OR status=''Restarted'') and NODE_NAME is NULL', '', 'more', '0', 'warn', 0, 3, 'schedules', 40);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cfg_queries`
--

CREATE TABLE IF NOT EXISTS `cfg_queries` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) collate utf8_unicode_ci NOT NULL,
  `label` varchar(70) collate utf8_unicode_ci NOT NULL,
  `tsmquery` varchar(500) collate utf8_unicode_ci NOT NULL,
  `fields` varchar(1000) collate utf8_unicode_ci NOT NULL,
  `timetablefields` varchar(255) collate utf8_unicode_ci default NULL,
  `info` varchar(500) collate utf8_unicode_ci default NULL,
  `orderby` varchar(35) collate utf8_unicode_ci default NULL,
  `alert_comp` varchar(35) collate utf8_unicode_ci default NULL,
  `alert_val` varchar(35) collate utf8_unicode_ci default NULL,
  `alert_field` varchar(35) collate utf8_unicode_ci default NULL,
  `alert_col` varchar(35) collate utf8_unicode_ci default NULL,
  `notforlibclient` tinyint(1) default NULL,
  `polltype` varchar(35) collate utf8_unicode_ci NOT NULL,
  `pollfreq` int(11) NOT NULL,
  `parent` varchar(35) collate utf8_unicode_ci NOT NULL,
  `sortorder` smallint(6) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=52 ;

--
-- Daten für Tabelle `cfg_queries`
--

INSERT INTO `cfg_queries` (`id`, `name`, `label`, `tsmquery`, `fields`, `timetablefields`, `info`, `orderby`, `alert_comp`, `alert_val`, `alert_field`, `alert_col`, `notforlibclient`, `polltype`, `pollfreq`, `parent`, `sortorder`) VALUES
(1, 'allnodes', 'All Nodes', 'select NODE_NAME, domain_name, platform_name, client_os_level, TCP_ADDRESS, trim(char(client_version)) ||''.''|| trim(char(client_release)) ||''.''|| trim(char(client_level))  ||''.''|| trim(char(client_sublevel)) as version from nodes', '`timestamp` int(11), `Node Name` varchar(64), `Domain` varchar(30), `OS` varchar(100), `OS Level` varchar(100), `IP` varchar(16), `Version` varchar(100)', NULL, 'This query lists all existing nodes, their domains etc.', 'Node Name', '', '', '', '', 0, 'update', 15, 'nodes', 10),
(2, 'inactivenodes', 'Inactive Nodes', 'select NODE_NAME, domain_name, platform_name, LASTACC_TIME from nodes where LASTACC_TIME < current_timestamp - 24 hours', '`timestamp` int(11), `Node Name` varchar(64), `Domain` varchar(30), `OS` varchar(100), `Last Accessed` varchar(26) ', NULL, 'A list of all nodes that haven''t been accessed since 24 hours.', 'Last Accessed', '', '', '', '', 0, 'update', 15, 'nodes', 20),
(3, 'filespaces', 'Filespaces', 'select nodes.node_name as NODENAME, filespaces.filespace_name as FSNAME, substr(char(filespaces.backup_end), 1, 10) as FS_LASTBACKUP from nodes, filespaces WHERE filespaces.node_name=nodes.node_name', '`timestamp` int(11), `Node Name` varchar(64), `Filespace` varchar(255), `Last Backup` varchar(26) ', NULL, 'A list of all filespaces and it''s last backup', 'Node Name', '', '', '', '', 0, 'update', 15, 'nodes', 30),
(4, 'stalledfilespaces', 'Stalled Filespaces', 'select node_name, filespace_name, backup_start, backup_end from filespaces where cast((current_timestamp-backup_end)days as decimal) >= 3', '`timestamp` int(11), `Node Name` varchar(64), `Filespace` varchar(255), `Backup Start` varchar(26), `Backup End` varchar(26) ', NULL, 'A list of all filespaces that might be stalled (and have not been backed up in the last 3 days)', 'Node Name', '', '', '', '', 0, 'update', 15, 'nodes', 40),
(5, 'spacenode', 'Space Usage / Node', 'SELECT o.node_name||'' '' as NODE_NAME, sum(cast(o.physical_mb/1024 as decimal (10,2))) as TOTAL_GB, sum(cast(o.num_files as decimal (10,0))) as TOTAL_FILES_STORED, dec(dec(sum(o.num_files))/(select dec(sum(num_files)) from occupancy)*100,6,4) as PCT_FILES_STORED, n.platform_name  FROM occupancy o,nodes n  WHERE o.node_name=n.node_name GROUP BY n.node_name, o.node_name, n.platform_name', '`timestamp` int(11), `Node Name` varchar(64), `Total GB` varchar(30), `Total Files stored` int(11), `Pct of total files` varchar(6), `Platform` varchar(100)', NULL, 'This query list all nodes and their total space consumption in GB', 'Total GB', '', '', '', '', 0, 'snapshot', 15, 'nodes', 50),
(6, 'datanode', 'Data and Tapes / Node', 'select vu.node_name, ao.total_mb, count(distinct vu.volume_name) as tapes, ao.total_mb/count(distinct vu.volume_name) as mbtape from volumeusage vu, auditocc ao where vu.node_name=ao.node_name group by vu.node_name, ao.total_mb', '`timestamp` int(11), `Node` varchar(64), `Total MB` int(30), `Tapes` int(30), `MB/Tape` int(30) ', NULL, 'This query shows how many data the nodes are using and over how many tapes they are spread.', 'Total MB', '', '', '', '', 0, 'snapshot', 15, 'nodes', 60),
(7, 'tapesnode', 'Tapes used by Node', 'select distinct node_name, volume_name,stgpool_name from volumeusage', '`timestamp` int(11), `Node Name` varchar(35), `Volume Name` varchar(100), `Storage Pool` varchar(30) ', NULL, 'Search for a node name and get all tapes that are used by ths node', 'Volume Name', '', '', '', '', 0, 'snapshot', 15, 'nodes', 70),
(8, 'serverinfo_vertical', 'Server Overview', 'select SERVER_NAME, SERVER_HLA, SERVER_LLA, INSTALL_DATE, RESTART_DATE, AVAILABILITY, LASTLICENSEAUDIT, LICENSECOMPLIANCE, LOGMODE, CROSSDEFINE, PLATFORM, VERSION, RELEASE, LEVEL, SUBLEVEL from status', '`timestamp` int(11), `Server Name` varchar(64), `IP` varchar(16), `port` varchar(5), `Installation Date/Time` varchar(26), `Restart Date/Time` varchar(26), `Available` varchar(30), `Last Lic Audit` varchar(26), `License Compliance` varchar(25), `Log Mode` varchar(25), `Crossdefine` varchar(10), `Platform` varchar(100), `Ver` varchar(100), `Rel` varchar(5), `Lvl` varchar(5), `SubLv` varchar(5) ', NULL, 'Some general information about the current tsm server', 'Server Name', '', '', '', '', 0, 'update', 15, 'server', 10),
(9, 'otherservers', 'Other Servers', 'select SERVER_NAME, HL_ADDRESS, LL_ADDRESS, DESCRIPTION, LASTACC_TIME from servers', '`timestamp` int(11), `Server Name` varchar(64), `IP` varchar(16), `port` varchar(5), `Desciption` varchar(255), `Last Access` varchar(26) ', NULL, 'Some information about other tsm servers known to the current one', 'Server Name', '', '', '', '', 0, 'update', 15, 'server', 20),
(10, 'database_vertical', 'Database', 'select AVAIL_SPACE_MB, CAPACITY_MB, MAX_EXTENSION_MB, MAX_REDUCTION_MB, PAGE_SIZE, USABLE_PAGES, USED_PAGES, PCT_UTILIZED, MAX_PCT_UTILIZED, PHYSICAL_VOLUMES, BUFF_POOL_PAGES, TOTAL_BUFFER_REQ, CACHE_HIT_PCT, CACHE_WAIT_PCT, NUM_BACKUP_INCR, BACKUP_CHG_MB, BACKUP_CHG_PCT, LAST_BACKUP_DATE, DB_REORG_EST, DB_REORG_EST_TIME from db', '`timestamp` int(11), `Avail Space MB` varchar(255), `Cty MB` varchar(255), `Max Ext MB` varchar(35), `Max Red MB` varchar(35), `Page Size` varchar(35), `Usable Pages` varchar(35), `Used Pages` varchar(35), `Pct. Util.` varchar(6), `Max Pct. Util.` varchar(6), `Phys Volumes` varchar(35), `Buff Pool Pages` varchar(35), `Total Buff Req` varchar(35), `Cache Hit Pct.` varchar(6), `Cache Wait` varchar(35), `Num Backup Incr` varchar(35), `Backup Chg MB` varchar(35), `Backup Chg Pct.` varchar(6), `Last Backup` varchar(26), `DB Reorg Est` varchar(35), `DB Reorg Est Time` varchar(35)', NULL, 'Some information about your tsm database', '', '', '', '', '', 0, 'snapshot', 15, 'server', 30),
(11, 'mgmtclasses', 'Management Classes', 'select DOMAIN_NAME, SET_NAME, CLASS_NAME, DEFAULTMC, DESCRIPTION from mgmtclasses', '`timestamp` int(11), `Domain` varchar(30), `Policy Set` varchar(30), `Class Name` varchar(30), `Default Class` varchar(3), `Description` varchar(255) ', NULL, 'A list of all management classes defined in the current tsm server', 'Domain', '', '', '', '', 0, 'update', 15, 'server', 40),
(12, 'policysets', 'Policy Sets', 'select DOMAIN_NAME, SET_NAME, DEFMGMTCLASS, DESCRIPTION from policysets', '`timestamp` int(11), `Domain` varchar(30), `Policy Set` varchar(30), `DEFMGMTCLASS` varchar(30), `Description` varchar(255) ', NULL, 'This is a list of all policy sets defined in the current tsm server', 'Domain', '', '', '', '', 0, 'update', 15, 'server', 50),
(13, 'domains', 'Domains', 'select DOMAIN_NAME, SET_LAST_ACTIVATED, DEFMGMTCLASS, NUM_NODES, BACKRETENTION, ARCHRETENTION, DESCRIPTION, ACTIVESTGPOOLS from domains', '`timestamp` int(11), `Domain` varchar(30), `Last Activated Set` varchar(30), `DEFMGMTCLASS` varchar(30), `# Nodes` int(11), `Backup Retention` int(11), `Archive Retention` int(11), `Description` varchar(255), `Active Stgpools` varchar(30) ', NULL, 'This is a list of all domains defined in the current tsm server', 'Domain', '', '', '', '', 0, 'update', 15, 'server', 60),
(14, 'backupcopygroups', 'Backup Copygroups', 'SELECT domain_name, class_name, VEREXISTS, VERDELETED, RETEXTRA, RETONLY, destination FROM bu_copygroups WHERE set_name=''ACTIVE''', '`timestamp` int(11), `Domain` varchar(30), `Managementclass` varchar(30), `VerExists` int(11), `VerDeleted` int(11), `RetExtra` int(11), `RetOnly` int(11), `Destination Pool` varchar(64) ', NULL, 'Destination pools and other information about all backup copygroups', 'Domain', '', '', '', '', 0, 'update', 15, 'server', 70),
(15, 'archivecopygroups', 'Archive Copygroups', 'SELECT domain_name, class_name, retver, destination FROM ar_copygroups where set_name=''ACTIVE''', '`timestamp` int(11), `Domain` varchar(30), `Managementclass` varchar(30), `RetVer` int(11), `Destination Pool` varchar(64) ', NULL, 'Destination pools and other information about all archive copygroups', 'Domain', '', '', '', '', 0, 'update', 15, 'server', 80),
(16, 'drives', 'Drives', 'select LIBRARY_NAME, DRIVE_NAME, DEVICE_TYPE, ONLINE, ELEMENT, DRIVE_STATE, ALLOCATED_TO, CLEAN_FREQ, DRIVE_SERIAL, VOLUME_NAME from drives', '`timestamp` int(11), `Library` varchar(30), `Name` varchar(30), `Type` varchar(30), `Online` varchar(3), `Element` varchar(30), `State` varchar(30), `Allocated To` varchar(30), `Clean Freq` varchar(30), `Serial #` varchar(30), `Volume` varchar(30) ', NULL, 'This is a list of all tape drives defined in the current tsm server', 'Name', 'equal', 'NO', 'Online', 'alarm', 1, 'update', 15, 'server', 90),
(17, 'paths', 'Paths', 'select SOURCE_NAME, SOURCE_TYPE, DESTINATION_NAME, DESTINATION_TYPE, LIBRARY_NAME, NODE_NAME, DEVICE, EXTERNAL_MANAGER, LUN, DIRECTORY, ONLINE from paths', '`timestamp` int(11), `Source Name` varchar(30), `Source Type` varchar(30), `Dest Name` varchar(30), `Dest Type` varchar(30), `Library` varchar(30), `Node Name` varchar(64), `Device` varchar(30), `External Manager` varchar(30), `Lun` varchar(30), `Directory` varchar(30), `Online` varchar(3) ', NULL, 'This is a list of all paths defined in the current tsm server', 'Source Name', 'equal', 'NO', 'Online', 'alarm', 1, 'update', 15, 'server', 100),
(18, 'drmedia', 'Disaster Recovery Media', 'select VOLUME_NAME, STGPOOL_NAME, LIB_NAME, VOLTYPE, UPD_DATE, LOCATION, STATE from drmedia', '`timestamp` int(11), `Volume Name` varchar(100), `Storage Pool` varchar(30), `Library` varchar(30), `Volume Type` varchar(30), `Last Update` varchar(26), `Location` varchar(30), `State` varchar(30) ', NULL, 'A list of all available DR media', 'volume Name', 'notequal', 'MOUNTABLE', 'State', 'alarm', 0, 'snapshot', 15, 'server', 110),
(19, 'backupstatus_24h', 'Backup Status / 24h', 'select Entity, Successful, cast(float(sum(bytes))/1024/1024/1024 as dec(8,3)) as GB, Examined, Affected, Failed from summary where activity=''BACKUP'' and cast((current_timestamp-start_time)hours as decimal(8,0)) LESS 24 group by Entity,Successful,Examined,AFFECTED,Failed', '`timestamp` int(11), `Entity` varchar(64), `Successful` varchar(3), `GB` varchar(30), `Examined` int(11), `Affected` int(11), `Failed` int(11), UNIQUE KEY `Entity` (`Entity`,`GB`,`Examined`,`Affected`,`Failed`)', NULL, 'A list of all backups that have been started during the last 24 hours', 'Successful', 'equal', 'NO', 'Successful', 'alarm', 0, 'update', 15, 'backup', 10),
(20, 'backups_all', 'Backup Times', 'SELECT entity AS "Node_name", CAST(sum(bytes/1024/1024/1024) AS decimal(8,3)) AS "GB_xfer", start_time, end_time, END_TIME-START_TIME as duration, comm_wait, Affected, Failed, Successful FROM summary WHERE activity=''BACKUP'' group by start_time, end_time, Successful,Examined,Affected,Failed,entity,comm_wait', '`timestamp` int(11), `Node Name` varchar(64), `TX/GB` varchar(30), `Start Time` varchar(30), `End Time` varchar(30), `Duration` varchar(30), `Comm Wait` int(11), `Files` int(11), `Failed` int(11), `OK?` varchar(3), UNIQUE KEY `Node Name` (`Node Name`,`TX/GB`,`Start Time`,`End Time`,`Comm Wait`) ', '`Node Name`, `Start Time`, `End Time`, `OK?`', 'Search for a node name and get a list of all backups performed on this node', 'Start Time', 'equal', 'NO', 'Successful', 'alarm', 0, 'append', 15, 'backup', 20),
(21, 'backups_24h', 'Backup Times / 24h', 'SELECT entity AS "Node_name", CAST(sum(bytes/1024/1024/1024) AS decimal(8,3)) AS "GB_xfer", start_time, end_time, END_TIME-START_TIME as duration, comm_wait, Affected, Failed, Successful FROM summary WHERE activity=''BACKUP'' AND start_time>=current_timestamp - 24 hours group by start_time, end_time, Successful,Examined,Affected,Failed,entity,comm_wait', '`timestamp` int(11), `Node Name` varchar(64), `TX/GB` varchar(30), `Start Time` varchar(26), `End Time` varchar(26), `Duration` varchar(30), `Comm Wait` int(11), `Files` int(11), `Failed` int(11), `OK?` varchar(3) ', '`Node Name`, `Start Time`, `End Time`, `OK?`', 'This query gives you an overview over all backups performed during the last 24 hours.', 'Start Time', 'equal', 'NO', 'Successful', 'alarm', 0, 'update', 15, 'backup', 30),
(23, 'archivestatus_24h', 'Archive Status / 24h', 'select Entity, Successful, cast(float(sum(bytes))/1024/1024/1024 as dec(8,3)) as GB, Examined, Affected, Failed from summary where activity=''ARCHIVE'' and cast((current_timestamp-start_time)hours as decimal(8,0)) LESS 24 group by Entity,Successful,Examined,AFFECTED,Failed', '`timestamp` int(11), `Entity` varchar(64), `Successful` varchar(3), `GB` varchar(30), `Examined` int(11), `Affected` int(11), `Failed` int(11), UNIQUE KEY `Entity` (`Entity`,`GB`,`Examined`,`Affected`,`Failed`) ', NULL, 'A list of all archives that have been started during the last 24 hours', 'Successful', 'equal', 'NO', 'Successful', 'alarm', 0, 'update', 15, 'backup', 60),
(24, 'archives_all', 'Archive Times', 'SELECT entity AS "Node_name", CAST(sum(bytes/1024/1024/1024) AS decimal(8,3)) AS "GB_xfer", start_time, end_time, END_TIME-START_TIME as duration, comm_wait, Affected, Failed, Successful FROM summary WHERE activity=''ARCHIVE'' group by start_time, end_time, Successful,Examined,Affected,Failed,entity,comm_wait', '`timestamp` int(11), `Node Name` varchar(64), `TX/GB` varchar(30), `Start Time` varchar(26), `End Time` varchar(26), `Duration` varchar(30), `Comm Wait` int(11), `Files` int(11), `Failed` int(11), `OK?` varchar(3), UNIQUE KEY `Node Name` (`Node Name`,`Start Time`,`End Time`,`Comm Wait`) ', '`Node Name`, `Start Time`, `End Time`, `OK?`', 'Search for a node name and get a list of all archives performed on this node', 'Start Time', 'equal', 'NO', 'Successful', 'alarm', 0, 'append', 15, 'backup', 70),
(25, 'archives_24', 'Archive Times / 24h', 'SELECT entity AS "Node_name", CAST(sum(bytes/1024/1024/1024) AS decimal(8,3)) AS "GB_xfer", start_time, end_time, END_TIME-START_TIME as duration, comm_wait, Affected, Failed, Successful FROM summary WHERE activity=''ARCHIVE'' AND start_time>=current_timestamp - 24 hours group by start_time, end_time, Successful,Examined,Affected,Failed,entity,comm_wait', '`timestamp` int(11), `Node Name` varchar(64), `TX/GB` varchar(30), `Start Time` varchar(26), `End Time` varchar(26), `Duration` varchar(30), `Comm Wait` int(11), `Files` int(11), `Failed` int(11), `OK?` varchar(3), UNIQUE KEY `Node Name` (`Node Name`,`Start Time`,`End Time`,`Comm Wait`) ', '`Node Name`, `Start Time`, `End Time`, `OK?`', 'This query gives you an overview over all archives performed during the last 24 hours.', 'Start Time', 'equal', 'NO', 'Successful', 'alarm', 0, 'append', 15, 'backup', 80),
(27, 'clientscheddef', 'Client Schedules Definitions', 'select schedule_name, domain_name, description, action, priority, starttime, period, perunits, dayofweek, sched_style from client_schedules', '`timestamp` int(11), `Schedule Name` varchar(30), `Domain` varchar(30), `Description` varchar(255), `Action` varchar(30), `Prio` int(2), `Start` varchar(26), `Period` int(2), `Unit` varchar(30), `Day of Week` varchar(30), `Style` varchar(30) ', NULL, 'A list of all client schedules defined in the current tsm server.', 'Schedule Name', '', '', '', '', 0, 'snapshot', 15, 'schedules', 10),
(28, 'clientschedules', 'Client Schedules Results', 'select node_name, schedule_name, scheduled_start, ACTUAL_START,COMPLETED, status, result, reason from events where scheduled_start >= current_timestamp - 24 hours and node_name != '''' and status != ''Future'' and status != ''Started''', '`timestamp` int(11), `Node Name` varchar(64), `Schedule Name` varchar(30), `Scheduled Start` varchar(26), `Actual Start` varchar(26), `End Time` varchar(26), `Status` varchar(30), `Result` int(11), `Reason` int(11), UNIQUE KEY `Node Name` (`Node Name`,`Schedule Name`,`Scheduled Start`,`Actual Start`,`End Time`)', '`Node Name`,`Actual Start`, `End Time`, `Status`', 'This query shows the start times and results of all client schedule that were scheduled during the last 24 hours.', 'Node Name', 'notequal', 'Completed', 'Status', 'alarm', 0, 'append', 15, 'schedules', 20),
(30, 'adminscheddef', 'Admin Schedules Definitions', 'select schedule_name, command, description, active, priority, starttime, period, perunits, dayofweek, sched_style from admin_schedules', '`timestamp` int(11), `Schedule Name` varchar(30), `Command` varchar(255), `Description` varchar(255), `Active?` varchar(3), `Prio` int(2), `Start` varchar(26), `Period` int(30), `Unit` varchar(30), `Day of Week` varchar(30), `Style` varchar(30) ', NULL, 'A list of all administrative schedules defined in the current tsm server.', 'Schedule Name', '', '', '', '', 0, 'snapshot', 15, 'schedules', 40),
(31, 'adminschedules', 'Admin Schedules Results', 'select schedule_name, scheduled_start, ACTUAL_START,COMPLETED,status, result, reason from events where scheduled_start >= current_timestamp - 24 hours and node_name is null and status != ''Future'' and status != ''Started''', '`timestamp` int(11), `Schedule Name` varchar(30), `Scheduled Start` varchar(26), `Actual Start` varchar(26), `End Time` varchar(26), `Status` varchar(30), `Result` int(11), `Reason` int(11), UNIQUE KEY `Schedule Name` (`Schedule Name`,`Scheduled Start`,`Actual Start`,`End Time`)', '`Schedule Name`, `Actual Start`, `End Time`, `Status`', 'This query shows the start times and results of all administrative schedule that were scheduled during the last 24 hours.', 'Schedule Name', 'notequal', 'Completed', 'Status', 'alarm', 0, 'append', 15, 'schedules', 50),
(33, 'assos', 'Assossiations', 'select domain_NAME, schedule_name, node_name, chg_time from associations', '`timestamp` int(11), `Domain` varchar(30), `Schedule` varchar(30), `Node` varchar(64), `Change Time` varchar(26) ', NULL, 'This query lists all defined schedule associations.', 'Domain', '', '', '', '', 0, 'update', 15, 'schedules', 70),
(34, 'migration', 'Migration', 'SELECT ENTITY, Activity, Start_Time as "Start_Time", End_Time, END_TIME-START_TIME as duration, Examined, Affected, cast((BYTES/1048576)/1024 as decimal(12,3)) as GB, FAILED, Mediaw, Processes, Successful FROM ADSM.SUMMARY where ACTIVITY like upper(''MIGRATION'') and date(start_time) >= current_date -1 days', '`timestamp` int(11), `Activity` varchar(30), `Process` varchar(30), `Start Time` varchar(26), `End Time` varchar(26), `Runtime` varchar(26), `Files/Dirs checked` int(11), `Files/Dirs transfered` int(11), `GB transfered` varchar(30), `Failures` int(1), `Mediaw` int(11), `Processes` int(11), `Successful` varchar(3), UNIQUE KEY `Activity` (`Activity`,`Process`,`Start Time`,`End Time`)', '`Activity`, `Start Time`, `End Time`, `Successful`', 'Information about all migration processes that ran during the last 24 hours.', 'Start Time', 'equal', 'NO', 'Successful', 'alarm', 0, 'append', 15, 'schedules', 80),
(36, 'processes', 'Running Processes', 'SELECT process_num, process, substr(char(start_time),1,19) AS START_TIME, substr(char(current_timestamp - start_time),1,10) as duration, cast(float(bytes_processed) /1024/1024 AS DEC(8,2)) AS MB, cast((cast(bytes_processed as dec(18,0))/cast((current_timestamp-start_time) seconds as decimal(18,0))) / 1024 / 1024 AS DEC (18,2)) as MBs, status FROM processes', '`timestamp` int(11), `Process Number` int(11), `Process` varchar(30), `Start Time` varchar(26), `ELAPTIME (D HHMMSS)` varchar(26), `MB` varchar(30), `MB/s` varchar(30), `Status` varchar(30) ', NULL, 'This query lists all running processes on the current tsm server.', 'Process Number', '', '', '', '', 0, 'snapshot', 2, 'activity', 10),
(37, 'sessions', 'Active Sessions', 'select SESSION_ID, substr(char(start_time),1,19) AS START_TIME, STATE, WAIT_SECONDS, cast(float(sum(BYTES_SENT))/1024/1024/1024 as dec(8,3)) as BSEND, cast(float(sum(BYTES_RECEIVED))/1024/1024/1024 as dec(8,3)) as received, CLIENT_NAME,CLIENT_PLATFORM, OUTPUT_VOL_ACCESS, INPUT_VOL_ACCESS, MOUNT_POINT_WAIT from sessions group by SESSION_ID, START_TIME, STATE, WAIT_SECONDS, CLIENT_NAME, INPUT_VOL_ACCESS, OUTPUT_VOL_ACCESS, MOUNT_POINT_WAIT, CLIENT_PLATFORM', '`timestamp` int(11), `Session` int(11), `Start Time` varchar(26), `State` varchar(30), `Wait (s)` int(11), `GB sent` varchar(30), `GB rcvd` varchar(30), `Node` varchar(64), `OS` varchar(100), `Output Volume` varchar(30), `Input Volume` varchar(30), `MP Wait` int(11) ', NULL, 'This query lists all active sessions on the current tsm server.', 'Session', '', '', '', '', 0, 'snapshot', 2, 'activity', 20),
(38, 'mounts', 'Mount Status', 'select LIBRARY_NAME, DRIVE_NAME, DRIVE_STATE, VOLUME_NAME, ALLOCATED_TO from drives where VOLUME_NAME is not null', '`timestamp` int(11), `Library` varchar(30), `Drive` varchar(30), `State` varchar(30), `Volume` varchar(30), `Allocated to` varchar(30) ', NULL, 'This query lists all mounted volumes.', 'Library', '', '', '', '', 1, 'snapshot', 5, 'activity', 30),
(39, 'mounthistory', 'Mount History', 'select DRIVE_NAME, START_TIME, END_TIME from summary where DRIVE_NAME NOTEQUAL '''' and end_time >=current_timestamp - 24 hours', '`timestamp` int(11), `Drive Name` varchar(30) ,`Start Time` varchar(26),`End Time` varchar(26), UNIQUE KEY `Drive Name` (`Drive Name`,`Start Time`,`End Time`)', '`Drive Name`, `Start Time` ,`End Time`', 'A graphical chart which represents all tape mounts during the last 24 hours.', 'Drive Name', '', '', '', '', 1, 'append', 15, 'activity', 40),
(40, 'warnings', 'Warnings', 'select DATE_TIME, MESSAGE from actlog where SEVERITY=''W'' ', '`timestamp` int(11), `Time` varchar(26), UNIQUE KEY `Time` (`Time`,`Message`), `Message` varchar(255) ', NULL, 'Warnings received from the tsm activity log.', '', '', '', '', '', 0, 'append', 15, 'activity', 50),
(41, 'errors', 'Errors', 'select DATE_TIME, MESSAGE from actlog where SEVERITY=''E'' ', '`timestamp` int(11), `Time` varchar(26), `Message` varchar(255), UNIQUE KEY `Time` (`Time`,`Message`) ', NULL, 'Errors received from the tsm activity log.', '', '', '', '', '', 0, 'append', 15, 'activity', 60),
(42, 'freescratch', 'Free Tapes', 'select count(*)Scratch from libvolumes where status=''Scratch''', '`timestamp` int(11), `Scratch` int(11) ', NULL, 'Gives you a number of scratch tapes known to the current tsm server.', '', 'less', '10', 'Scratch', 'alarm', 1, 'snapshot', 15, 'serverstorage', 10),
(43, 'freescratch_pool', 'Free Tapes / Pool', 'SELECT STGPOOLS.STGPOOL_NAME, STGPOOLS.MAXSCRATCH, Count(STGPOOLS.MAXSCRATCH) as "Allocated_SCRATCH", STGPOOLS.MAXSCRATCH-count(STGPOOLS.MAXSCRATCH) as "Remaining_SCRATCH" FROM STGPOOLS,VOLUMES WHERE (VOLUMES.STGPOOL_NAME = STGPOOLS.STGPOOL_NAME) AND ((STGPOOLS.DEVCLASS=''LTO3DEVCL'')) GROUP BY STGPOOLS.STGPOOL_NAME, STGPOOLS.MAXSCRATCH', '`timestamp` int(11), `Storagepool` varchar(30), `MAXSCRATCH` int(11) ,`Allocated Scratch` int(11), `Remaining Scratch` int(11) ', NULL, 'Shows how many scratch tapes are available per pool. The numbers shown here are only defined max. values, not actual free tapes!', 'Storagepool', 'equal', '0', 'Remaining Scratch', 'alarm', 0, 'update', 15, 'serverstorage', 20),
(44, 'stgpools', 'Storagepools', 'select STGPOOL_NAME, DEVCLASS, EST_CAPACITY_MB, PCT_UTILIZED, PCT_MIGR, HIGHMIG, LOWMIG, MIGR_RUNNING, NEXTSTGPOOL from stgpools', '`timestamp` int(11), `Storagepool` varchar(30), `DevClass` varchar(30), `Capacity (MB)` varchar(30), `Pct util` varchar(6), `Pct mig` varchar(6), `high mig` int(11), `low mig` int(11), `mig running` varchar(3), `Next Storagepool` varchar(30) ', NULL, 'Lists all storage pools', 'Storagepool', 'equal', 'YES', 'mig running', 'warn', 0, 'snapshot', 15, 'serverstorage', 30),
(45, 'spacereclamation', 'Space Reclamation', 'select volume_name, pct_reclaim, stgpool_name from volumes where pct_reclaim >= 60', '`timestamp` int(11), `Volume Name` varchar(100), `Pct reclaim` varchar(6), `Storage Pool` varchar(30) ', NULL, 'A list of volumes that could be reclaimed.', 'Pct reclaim', '', '', '', '', 0, 'update', 15, 'serverstorage', 40),
(46, 'errortapes', 'Error Tapes', 'select VOLUME_NAME, ACCESS from volumes where access !=''READWRITE''', '`timestamp` int(11), `Volume Name` varchar(30), `Access` varchar(30) ', NULL, 'A list of volumes that are not in READWRITE state.', 'Volume Name', '', '', '', '', 0, 'snapshot', 15, 'serverstorage', 50),
(47, 'volumes', 'Volumes', 'select volume_name, stgpool_name, est_capacity_mb, pct_utilized, status, access, error_state, write_errors, read_errors from volumes', '`timestamp` int(11), `Volume Name` varchar(100), `Storage Pool` varchar(30), `Capacity (MB)` varchar(30), `Usage (Pct)` varchar(6), `Status` varchar(30), `Access` varchar(30), `Err State` varchar(30), `Write Err` varchar(30), `Read Err` varchar(30) ', NULL, 'A list of all volumes known to this tsm server.', 'Volume Name', 'notequal', 'READWRITE', 'Access', 'alarm', 0, 'update', 15, 'serverstorage', 60),
(48, 'libvolumes', 'Library Volumes', 'select LIBRARY_NAME, VOLUME_NAME, STATUS, OWNER, LAST_USE, DEVTYPE, MEDIATYPE from libvolumes', '`timestamp` int(11), `Library` varchar(30), `Volume Name` varchar(100), `Status` varchar(30), `Owner` varchar(30), `Last Use` varchar(26), `Device Type` varchar(30), `Media Type` varchar(30) ', NULL, 'An inventory of all libraries attached to this tsm server.', 'Library', '', '', '', '', 1, 'update', 15, 'serverstorage', 70),
(49, 'backupsets', 'Backupsets', 'select NODE_NAME, BACKUPSET_NAME, DATE_TIME, DESCRIPTION from backupsets', '`timestamp` int(11), `Node Name` varchar(64), `Backupset Name` varchar(30), `Date` varchar(26), `Description` varchar(255) ', NULL, 'A list of all active Backupsets.', 'Node Name', '', '', '', '', 0, 'snapshot', 15, 'offsite', 10),
(50, 'exports', 'Exports', 'select DATE_TIME, VOLUME_SEQ, DEVCLASS, VOLUME_NAME, LOCATION, COMMAND from volhistory where TYPE=''EXPORT''', '`timestamp` int(11), `Date` varchar(26), `SEQ` varchar(10), `Dev Class` varchar(100), `Volume Name` varchar(100), `Location` varchar(255), `Command` varchar(255) ', NULL, 'A list of all Exports.', 'Date', '', '', '', '', 0, 'snapshot', 15, 'offsite', 20),
(51, 'offsitetapes', 'Offsite Tapes', 'select VOLUME_NAME, TYPE, VOLUME_SEQ, DATE_TIME, COMMAND from volhistory where type=''BACKUPSET'' or type=''EXPORT'' and volume_name not in (select volume_name from libvolumes)', '`timestamp` int(11), `Volume Name` varchar(100), `Type` varchar(30), `Vol Seq` int(11), `Date/Time` varchar(26), `Command` varchar(255) ', NULL, 'A list of your offsite tapes (all backupsets and exports that are currently not in you tape library)', 'Date/Time', '', '', '', '', 0, 'snapshot', 15, 'offsite', 30);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cfg_servers`
--

CREATE TABLE IF NOT EXISTS `cfg_servers` (
  `id` int(11) NOT NULL auto_increment,
  `servername` varchar(35) collate utf8_unicode_ci NOT NULL,
  `description` varchar(35) collate utf8_unicode_ci NOT NULL,
  `ip` varchar(15) collate utf8_unicode_ci NOT NULL,
  `port` int(5) NOT NULL,
  `username` varchar(35) collate utf8_unicode_ci NOT NULL,
  `password` varchar(35) collate utf8_unicode_ci NOT NULL,
  `libraryclient` int(1) NOT NULL default '0',
  `default` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5 ;

--
-- Daten für Tabelle `cfg_servers`
--

INSERT INTO `cfg_servers` (`id`, `servername`, `description`, `ip`, `port`, `username`, `password`, `libraryclient`, `default`) VALUES
(1, 'TSMSRV1', 'AIX', '172.18.111.1', 1500, 'tsmmon', 'tsmmon', 0, 1),
(2, 'TSMSRV2', 'YYY', '172.16.111.2', 1500, 'tsmmon', 'tsmmon', 1, 0),
(3, 'TSMSRV3', 'XXX', '172.16.111.3', 1500, 'tsmmon', 'tsmmon', 1, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cfg_users`
--

CREATE TABLE IF NOT EXISTS `cfg_users` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(35) collate utf8_unicode_ci NOT NULL,
  `password` varchar(32) collate utf8_unicode_ci NOT NULL,
  `stylesheet` varchar(35) collate utf8_unicode_ci NOT NULL default 'default.css',
  `role` varchar(35) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=16 ;

--
-- Daten für Tabelle `cfg_users`
--

INSERT INTO `cfg_users` (`id`, `username`, `password`, `stylesheet`, `role`) VALUES
(1, 'admin', '21232f297a57a5a743894a0e4a801fc3', 'style_silver.css', 'admin');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_hashes`
--

CREATE TABLE IF NOT EXISTS `log_hashes` (
  `tablename` varchar(255) collate utf8_unicode_ci NOT NULL,
  `hash` varchar(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`tablename`),
  UNIQUE KEY `tablename` (`tablename`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_polldlog`
--

CREATE TABLE IF NOT EXISTS `log_polldlog` (
  `timestamp` int(11) NOT NULL,
  `servername` varchar(30) collate utf8_unicode_ci NOT NULL,
  `Updated` int(11) NOT NULL,
  `Not Changed` int(11) NOT NULL,
  `Pollfreq Not Reached` int(11) NOT NULL,
  `timeneeded` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_polldstat`
--

CREATE TABLE IF NOT EXISTS `log_polldstat` (
  `id` tinyint(1) NOT NULL default '1',
  `enabled` int(1) NOT NULL default '1',
  `status` varchar(35) collate utf8_unicode_ci NOT NULL,
  `lastrun` int(11) default NULL,
  `nextrun` int(11) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Daten für Tabelle `log_polldstat`
--

INSERT INTO `log_polldstat` (`id`, `enabled`, `status`, `lastrun`, `nextrun`) VALUES
(1, 0, '', 0, 0);
