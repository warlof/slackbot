/*
Navicat MySQL Data Transfer

Source Server         : Babymagnum
Source Server Version : 50630
Source Host           : 192.168.1.20:3306
Source Database       : seat_test

Target Server Type    : MYSQL
Target Server Version : 50630
File Encoding         : 65001

Date: 2016-08-09 23:22:54
*/

SET FOREIGN_KEY_CHECKS=0;

USE seat_test;

-- ----------------------------
-- Table structure for account_account_statuses
-- ----------------------------
DROP TABLE IF EXISTS `account_account_statuses`;
CREATE TABLE `account_account_statuses` (
  `keyID` int(11) NOT NULL,
  `paidUntil` datetime NOT NULL,
  `createDate` datetime NOT NULL,
  `logonCount` int(11) NOT NULL,
  `logonMinutes` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`keyID`),
  UNIQUE KEY `account_account_statuses_keyid_unique` (`keyID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for account_api_key_info_characters
-- ----------------------------
DROP TABLE IF EXISTS `account_api_key_info_characters`;
CREATE TABLE `account_api_key_info_characters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `keyID` int(11) NOT NULL,
  `characterID` int(11) NOT NULL,
  `characterName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `corporationID` int(11) NOT NULL,
  `corporationName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `account_api_key_info_characters_keyid_index` (`keyID`),
  KEY `account_api_key_info_characters_characterid_index` (`characterID`),
  KEY `account_api_key_info_characters_charactername_index` (`characterName`)
) ENGINE=InnoDB AUTO_INCREMENT=288 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for account_api_key_infos
-- ----------------------------
DROP TABLE IF EXISTS `account_api_key_infos`;
CREATE TABLE `account_api_key_infos` (
  `keyID` int(11) NOT NULL,
  `accessMask` bigint(20) NOT NULL,
  `type` enum('Account','Character','Corporation') COLLATE utf8_unicode_ci NOT NULL,
  `expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`keyID`),
  UNIQUE KEY `account_api_key_infos_keyid_unique` (`keyID`),
  KEY `account_api_key_infos_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for affiliation_role
-- ----------------------------
DROP TABLE IF EXISTS `affiliation_role`;
CREATE TABLE `affiliation_role` (
  `role_id` int(11) NOT NULL,
  `affiliation_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for affiliation_user
-- ----------------------------
DROP TABLE IF EXISTS `affiliation_user`;
CREATE TABLE `affiliation_user` (
  `user_id` int(11) NOT NULL,
  `affiliation_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for affiliations
-- ----------------------------
DROP TABLE IF EXISTS `affiliations`;
CREATE TABLE `affiliations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `affiliation` int(11) NOT NULL,
  `type` enum('char','corp') COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for api_token_logs
-- ----------------------------
DROP TABLE IF EXISTS `api_token_logs`;
CREATE TABLE `api_token_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `api_token_id` int(11) DEFAULT NULL,
  `action` enum('allow','deny') COLLATE utf8_unicode_ci NOT NULL,
  `request_path` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `src_ip` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `api_token_logs_api_token_id_index` (`api_token_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1371 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for api_tokens
-- ----------------------------
DROP TABLE IF EXISTS `api_tokens`;
CREATE TABLE `api_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `allowed_src` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `api_tokens_token_index` (`token`),
  KEY `api_tokens_allowed_src_index` (`allowed_src`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for character_character_sheet_corporation_titles
-- ----------------------------
DROP TABLE IF EXISTS `character_character_sheet_corporation_titles`;
CREATE TABLE `character_character_sheet_corporation_titles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `characterID` int(11) NOT NULL,
  `titleID` int(11) NOT NULL,
  `titleName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `character_character_sheet_corporation_titles_characterid_index` (`characterID`)
) ENGINE=InnoDB AUTO_INCREMENT=393264 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for character_character_sheets
-- ----------------------------
DROP TABLE IF EXISTS `character_character_sheets`;
CREATE TABLE `character_character_sheets` (
  `characterID` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `homeStationID` int(11) NOT NULL,
  `DoB` datetime NOT NULL,
  `race` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `bloodLineID` int(11) NOT NULL,
  `bloodLine` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ancestryID` int(11) NOT NULL,
  `ancestry` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `gender` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `corporationName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `corporationID` int(11) NOT NULL,
  `allianceName` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `allianceID` int(11) DEFAULT NULL,
  `factionName` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `factionID` int(11) NOT NULL,
  `cloneTypeID` int(11) NOT NULL,
  `cloneName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `cloneSkillPoints` int(11) NOT NULL,
  `freeSkillPoints` int(11) NOT NULL,
  `freeRespecs` int(11) NOT NULL,
  `cloneJumpDate` datetime NOT NULL,
  `lastRespecDate` datetime NOT NULL,
  `lastTimedRespec` datetime NOT NULL,
  `remoteStationDate` datetime NOT NULL,
  `jumpActivation` datetime NOT NULL,
  `jumpFatigue` datetime NOT NULL,
  `jumpLastUpdate` datetime NOT NULL,
  `balance` decimal(30,2) DEFAULT NULL,
  `intelligence` int(11) NOT NULL,
  `memory` int(11) NOT NULL,
  `charisma` int(11) NOT NULL,
  `perception` int(11) NOT NULL,
  `willpower` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`characterID`),
  UNIQUE KEY `character_character_sheets_characterid_unique` (`characterID`),
  KEY `character_character_sheets_corporationid_index` (`corporationID`),
  KEY `character_character_sheets_allianceid_index` (`allianceID`),
  KEY `character_character_sheets_name_index` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for corporation_sheets
-- ----------------------------
DROP TABLE IF EXISTS `corporation_sheets`;
CREATE TABLE `corporation_sheets` (
  `corporationID` int(11) NOT NULL,
  `corporationName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ticker` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ceoID` int(11) NOT NULL,
  `ceoName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `stationID` int(11) NOT NULL,
  `stationName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `allianceID` int(11) DEFAULT NULL,
  `factionID` int(11) DEFAULT NULL,
  `allianceName` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `taxRate` decimal(30,2) NOT NULL,
  `memberCount` int(11) NOT NULL,
  `memberLimit` int(11) NOT NULL,
  `shares` int(11) NOT NULL,
  `graphicID` int(11) NOT NULL,
  `shape1` int(11) NOT NULL,
  `shape2` int(11) NOT NULL,
  `shape3` int(11) NOT NULL,
  `color1` int(11) NOT NULL,
  `color2` int(11) NOT NULL,
  `color3` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`corporationID`),
  UNIQUE KEY `corporation_sheets_corporationid_unique` (`corporationID`),
  KEY `corporation_sheets_corporationname_index` (`corporationName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for eve_alliance_list_member_corporations
-- ----------------------------
DROP TABLE IF EXISTS `eve_alliance_list_member_corporations`;
CREATE TABLE `eve_alliance_list_member_corporations` (
  `allianceID` int(11) NOT NULL,
  `corporationID` int(11) NOT NULL,
  `startDate` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  KEY `eve_alliance_list_member_corporations_allianceid_index` (`allianceID`),
  KEY `eve_alliance_list_member_corporations_corporationid_index` (`corporationID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for eve_alliance_lists
-- ----------------------------
DROP TABLE IF EXISTS `eve_alliance_lists`;
CREATE TABLE `eve_alliance_lists` (
  `allianceID` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `shortName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `executorCorpID` int(11) NOT NULL,
  `memberCount` int(11) NOT NULL,
  `startDate` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`allianceID`),
  UNIQUE KEY `eve_alliance_lists_allianceid_unique` (`allianceID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for eve_api_keys
-- ----------------------------
DROP TABLE IF EXISTS `eve_api_keys`;
CREATE TABLE `eve_api_keys` (
  `key_id` int(11) NOT NULL,
  `v_code` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  `last_error` text COLLATE utf8_unicode_ci,
  `disabled_calls` longtext COLLATE utf8_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`key_id`),
  UNIQUE KEY `eve_api_keys_key_id_unique` (`key_id`),
  KEY `eve_api_keys_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for failed_jobs
-- ----------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `connection` text COLLATE utf8_unicode_ci NOT NULL,
  `queue` text COLLATE utf8_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for global_settings
-- ----------------------------
DROP TABLE IF EXISTS `global_settings`;
CREATE TABLE `global_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `global_settings_name_index` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for job_trackings
-- ----------------------------
DROP TABLE IF EXISTS `job_trackings`;
CREATE TABLE `job_trackings` (
  `job_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `owner_id` int(11) NOT NULL DEFAULT '0',
  `api` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `scope` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `output` text COLLATE utf8_unicode_ci,
  `status` enum('Queued','Working','Done','Error') COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`job_id`),
  UNIQUE KEY `job_trackings_job_id_unique` (`job_id`),
  KEY `job_trackings_owner_id_index` (`owner_id`),
  KEY `job_trackings_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for jobs
-- ----------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8_unicode_ci NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_reserved_reserved_at_index` (`queue`,`reserved`,`reserved_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for migrations
-- ----------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `migration` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for role_user
-- ----------------------------
DROP TABLE IF EXISTS `role_user`;
CREATE TABLE `role_user` (
  `role_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for roles
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for schedules
-- ----------------------------
DROP TABLE IF EXISTS `schedules`;
CREATE TABLE `schedules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `command` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `expression` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `allow_overlap` tinyint(1) NOT NULL DEFAULT '0',
  `allow_maintenance` tinyint(1) NOT NULL DEFAULT '0',
  `ping_before` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ping_after` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for slack_channel_alliances
-- ----------------------------
DROP TABLE IF EXISTS `slack_channel_alliances`;
CREATE TABLE `slack_channel_alliances` (
  `alliance_id` int(11) NOT NULL,
  `channel_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `enable` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`alliance_id`,`channel_id`),
  KEY `slack_channel_alliances_channel_id_foreign` (`channel_id`),
  CONSTRAINT `slack_channel_alliances_ibfk_1` FOREIGN KEY (`alliance_id`) REFERENCES `eve_alliance_lists` (`allianceID`) ON DELETE CASCADE,
  CONSTRAINT `slack_channel_alliances_ibfk_2` FOREIGN KEY (`channel_id`) REFERENCES `slack_channels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for slack_channel_corporations
-- ----------------------------
DROP TABLE IF EXISTS `slack_channel_corporations`;
CREATE TABLE `slack_channel_corporations` (
  `corporation_id` int(11) NOT NULL,
  `channel_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `enable` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`corporation_id`,`channel_id`),
  KEY `slack_channel_corporations_channel_id_foreign` (`channel_id`),
  CONSTRAINT `slack_channel_corporations_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `slack_channels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for slack_channel_public
-- ----------------------------
DROP TABLE IF EXISTS `slack_channel_public`;
CREATE TABLE `slack_channel_public` (
  `channel_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `enable` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`channel_id`),
  CONSTRAINT `slack_channel_public_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `slack_channels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for slack_channel_roles
-- ----------------------------
DROP TABLE IF EXISTS `slack_channel_roles`;
CREATE TABLE `slack_channel_roles` (
  `role_id` int(10) unsigned NOT NULL,
  `channel_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `enable` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`role_id`,`channel_id`),
  KEY `slack_channel_roles_channel_id_foreign` (`channel_id`),
  CONSTRAINT `slack_channel_roles_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `slack_channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `slack_channel_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for slack_channel_users
-- ----------------------------
DROP TABLE IF EXISTS `slack_channel_users`;
CREATE TABLE `slack_channel_users` (
  `user_id` int(10) unsigned NOT NULL,
  `channel_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `enable` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`user_id`,`channel_id`),
  KEY `slack_channel_users_channel_id_foreign` (`channel_id`),
  CONSTRAINT `slack_channel_users_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `slack_channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `slack_channel_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for slack_channels
-- ----------------------------
DROP TABLE IF EXISTS `slack_channels`;
CREATE TABLE `slack_channels` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `is_group` tinyint(1) NOT NULL DEFAULT '0',
  `is_general` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for slack_logs
-- ----------------------------
DROP TABLE IF EXISTS `slack_logs`;
CREATE TABLE `slack_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `message` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=396033 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for slack_oauth
-- ----------------------------
DROP TABLE IF EXISTS `slack_oauth`;
CREATE TABLE `slack_oauth` (
  `client_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `client_secret` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `state` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for slack_users
-- ----------------------------
DROP TABLE IF EXISTS `slack_users`;
CREATE TABLE `slack_users` (
  `user_id` int(10) unsigned NOT NULL,
  `slack_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `invited` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`user_id`),
  CONSTRAINT `slack_users_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for user_settings
-- ----------------------------
DROP TABLE IF EXISTS `user_settings`;
CREATE TABLE `user_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `user_settings_user_id_index` (`user_id`),
  KEY `user_settings_name_index` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=202 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `eve_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `last_login` datetime DEFAULT NULL,
  `last_login_source` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET FOREIGN_KEY_CHECKS=1;
