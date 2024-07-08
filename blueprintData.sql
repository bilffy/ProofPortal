-- MySQL dump 10.13  Distrib 8.0.36, for Win64 (x86_64)
--
-- Host: percona-prod03.msp.local    Database: blueprint
-- ------------------------------------------------------
-- Server version	8.0.35-27

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `changelogs`
--

DROP TABLE IF EXISTS `changelogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `changelogs` (
  `changelog_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `ts_jobkey` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keyvalue` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keyorigin` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `change_from` text COLLATE utf8mb4_unicode_ci,
  `change_to` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `resolved_status_id` int unsigned DEFAULT NULL,
  `issue_id` int unsigned DEFAULT NULL,
  `change_datetime` datetime DEFAULT NULL,
  `decision_datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`changelog_id`),
  KEY `changelogs_user_id_foreign` (`user_id`),
  KEY `changelogs_resolved_status_id_foreign` (`resolved_status_id`),
  KEY `changelogs_issue_id_foreign` (`issue_id`),
  CONSTRAINT `changelogs_issue_id_foreign` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`issue_id`) ON DELETE CASCADE,
  CONSTRAINT `changelogs_resolved_status_id_foreign` FOREIGN KEY (`resolved_status_id`) REFERENCES `status` (`status_id`) ON DELETE CASCADE,
  CONSTRAINT `changelogs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `changelogs`
--

LOCK TABLES `changelogs` WRITE;
/*!40000 ALTER TABLE `changelogs` DISABLE KEYS */;
/*!40000 ALTER TABLE `changelogs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_categories`
--

DROP TABLE IF EXISTS `email_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_categories` (
  `email_category_id` int unsigned NOT NULL AUTO_INCREMENT,
  `email_category_name` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`email_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_categories`
--

LOCK TABLES `email_categories` WRITE;
/*!40000 ALTER TABLE `email_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `emails`
--

DROP TABLE IF EXISTS `emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emails` (
  `email_id` int unsigned NOT NULL AUTO_INCREMENT,
  `generated_from_user_id` int unsigned DEFAULT NULL,
  `alphacode` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ts_jobkey` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ts_schoolkey` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sentdate` datetime DEFAULT NULL,
  `completed` datetime DEFAULT NULL,
  `email_from` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_to` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_cc` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_bcc` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_content` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_code` int DEFAULT NULL,
  `smtp_message` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_token` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `template_id` int unsigned DEFAULT NULL,
  `email_category_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`email_id`),
  KEY `emails_generated_from_user_id_foreign` (`generated_from_user_id`),
  KEY `emails_template_id_foreign` (`template_id`),
  KEY `emails_email_category_id_foreign` (`email_category_id`),
  CONSTRAINT `emails_email_category_id_foreign` FOREIGN KEY (`email_category_id`) REFERENCES `email_categories` (`email_category_id`) ON DELETE CASCADE,
  CONSTRAINT `emails_generated_from_user_id_foreign` FOREIGN KEY (`generated_from_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `emails_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `templates` (`template_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emails`
--

LOCK TABLES `emails` WRITE;
/*!40000 ALTER TABLE `emails` DISABLE KEYS */;
/*!40000 ALTER TABLE `emails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `folder_tags`
--

DROP TABLE IF EXISTS `folder_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folder_tags` (
  `folder_tag_id` int unsigned NOT NULL AUTO_INCREMENT,
  `internal_tag` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_tag` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`folder_tag_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `folder_tags`
--

LOCK TABLES `folder_tags` WRITE;
/*!40000 ALTER TABLE `folder_tags` DISABLE KEYS */;
INSERT INTO `folder_tags` VALUES (1,'sp','Speciality Group'),(2,'f','Family'),(3,'families','Family'),(4,'staff','Staff'),(5,'students','Students'),(6,'sibilings','Family'),(7,'sibiling','Family');
/*!40000 ALTER TABLE `folder_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `folders`
--

DROP TABLE IF EXISTS `folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folders` (
  `folder_id` int unsigned NOT NULL AUTO_INCREMENT,
  `ts_folder_id` int DEFAULT NULL,
  `ts_folderkey` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ts_foldername` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ts_job_id` int DEFAULT NULL,
  `folder_tag_id` int unsigned DEFAULT NULL,
  `status_id` int unsigned DEFAULT NULL,
  `teacher` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `principal` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deputy` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_edit_portraits` int DEFAULT NULL,
  `is_edit_groups` int DEFAULT NULL,
  PRIMARY KEY (`folder_id`),
  KEY `folders_folder_tag_id_foreign` (`folder_tag_id`),
  KEY `folders_status_id_foreign` (`status_id`),
  CONSTRAINT `folders_folder_tag_id_foreign` FOREIGN KEY (`folder_tag_id`) REFERENCES `folder_tags` (`folder_tag_id`) ON DELETE CASCADE,
  CONSTRAINT `folders_status_id_foreign` FOREIGN KEY (`status_id`) REFERENCES `status` (`status_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `folders`
--

LOCK TABLES `folders` WRITE;
/*!40000 ALTER TABLE `folders` DISABLE KEYS */;
INSERT INTO `folders` VALUES (1,7167332,'3LXU7CLS','Year 7-8F - 2024',291829,1,1,'Sophie Paul','Geoge Philip','Thomas Mathew',NULL,NULL);
/*!40000 ALTER TABLE `folders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `franchise_users`
--

DROP TABLE IF EXISTS `franchise_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `franchise_users` (
  `franchise_user_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `franchise_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`franchise_user_id`),
  KEY `franchise_users_user_id_foreign` (`user_id`),
  KEY `franchise_users_franchise_id_foreign` (`franchise_id`),
  CONSTRAINT `franchise_users_franchise_id_foreign` FOREIGN KEY (`franchise_id`) REFERENCES `franchises` (`franchise_id`) ON DELETE CASCADE,
  CONSTRAINT `franchise_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `franchise_users`
--

LOCK TABLES `franchise_users` WRITE;
/*!40000 ALTER TABLE `franchise_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `franchise_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `franchises`
--

DROP TABLE IF EXISTS `franchises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `franchises` (
  `franchise_id` int unsigned NOT NULL AUTO_INCREMENT,
  `ts_account_id` int DEFAULT NULL,
  `alphacode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postcode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `suburb` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_id` int DEFAULT NULL,
  PRIMARY KEY (`franchise_id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `franchises`
--

LOCK TABLES `franchises` WRITE;
/*!40000 ALTER TABLE `franchises` DISABLE KEYS */;
INSERT INTO `franchises` VALUES (1,6,'ADLNTH','Adelaide North','PO Box 3292 Norwood','5067',NULL,'SA','Australia',1),(2,7,'ADLSTH','Adelaide South','PO Box 23 Mount Barker','5251',NULL,'SA','Australia',1),(3,3,'BALLT','Ballarat','890 Humffray Street South Mt Pleasant','3350',NULL,'VIC','Australia',1),(4,8,'BRISB','Brisbane','PO Box 113 Northgate','4013',NULL,'QLD','Australia',1),(5,9,'CANBRA','Canberra','PO Box 293 Jerrabomberra','2619',NULL,'ACT','Australia',1),(6,10,'CENTQ','Central Queensland','PO Box 293 Rockhampton','4700',NULL,'QLD','Australia',1),(7,11,'CENTW','Central West NSW','PO Box 8637 Orange','2800',NULL,'NSW','Australia',1),(8,29,'COUSA','Country SA','152 Murray Street Gawler','5118',NULL,'SA','Australia',1),(9,21,'GCNR','GCNR','Suite 5, 37 Bundall Road Surfers Paradise','4217',NULL,'QLD','Australia',1),(10,12,'HUNTER','Hunter - Central Coast','PO Box 797 Maitland','2320',NULL,'NSW','Australia',1),(11,13,'ILLAA','Illawarra','PO Box 218 Dapto','2530',NULL,'NSW','Australia',1),(12,1,'RC','Resource Centre','2 Ball Place Wagga Wagga NSW 2650',NULL,NULL,NULL,'Australia',1),(13,14,'MTGAM','Mt Gambier','53 Ferrers Street Mt Gambier','5290',NULL,'SA','Australia',1),(14,15,'MURIV','Murray River Region','PO Box 21 Moama','2731',NULL,'VIC','Australia',1),(15,16,'NEWENG','New England','PO Box 1231 Armidale','2350',NULL,'NSW','Australia',1),(16,17,'NTHCOST','North Coast','PO Box 422 Port Macquarie','2444',NULL,'NSW','Australia',1),(17,19,'NEMELB','North East Melbourne','7/10 Mirra Court Bundoora','3083',NULL,'VIC','Australia',1),(18,18,'NTHQLD','North Queensland','161 Ross River Road Mundingburra','4812',NULL,'QLD','Australia',1),(19,20,'RRINA','Riverina','PO Box 5813 Wagga Wagga','2650',NULL,'NSW','Australia',1),(20,32,'SEQ','SEQ','PO Box 202 SALISBURY','4107',NULL,'QLD','Australia',1),(21,22,'STHSYD','South Sydney','PO Box 112 Earlwood','2206',NULL,'NSW','Australia',1),(22,23,'STHVIC','Southern Victoria','31-33 Wattlepark Avenue Geelong','3220',NULL,'VIC','Australia',1),(23,25,'SYDNEY','Sydney','PO Box 850 Rozelle','2039',NULL,'NSW','Australia',1),(24,26,'SYDWST','Sydney West','PO Box 427 Winston Hills','2153',NULL,'NSW','Australia',1),(25,27,'TASMA','Tasmania','8 Lefroy Street North Hobart','7000',NULL,'TAS','Australia',1),(26,30,'TRAINING','Training','2 Ball Place Wagga Wagga NSW 2650',NULL,NULL,NULL,'Australia',1),(27,28,'WESTAU','WA','Unit 3 168 Balcatta Road  Warwick','6024',NULL,'WA','Australia',1),(28,34,'MELB','Melbourne','PO Box 366 Balwyn North','3104',NULL,'VIC','Australia',1);
/*!40000 ALTER TABLE `franchises` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_positions`
--

DROP TABLE IF EXISTS `group_positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_positions` (
  `group_position_id` int unsigned NOT NULL AUTO_INCREMENT,
  `ts_jobkey` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ts_folderkey` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_fullname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `row_description` text COLLATE utf8mb4_unicode_ci,
  `row_number` int DEFAULT NULL,
  `row_position` int DEFAULT NULL,
  `ts_defaultGroup` int DEFAULT NULL,
  PRIMARY KEY (`group_position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_positions`
--

LOCK TABLES `group_positions` WRITE;
/*!40000 ALTER TABLE `group_positions` DISABLE KEYS */;
/*!40000 ALTER TABLE `group_positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `image_types`
--

DROP TABLE IF EXISTS `image_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `image_types` (
  `image_type_id` int unsigned NOT NULL AUTO_INCREMENT,
  `type_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`image_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `image_types`
--

LOCK TABLES `image_types` WRITE;
/*!40000 ALTER TABLE `image_types` DISABLE KEYS */;
INSERT INTO `image_types` VALUES (1,'Portrait'),(2,'Group'),(3,'Special Events'),(4,'Promo Photo'),(5,'School Photo');
/*!40000 ALTER TABLE `image_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `images`
--

DROP TABLE IF EXISTS `images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `images` (
  `image_id` int unsigned NOT NULL AUTO_INCREMENT,
  `ts_image_id` int DEFAULT NULL,
  `ts_imagekey` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ts_job_id` int DEFAULT NULL,
  `keyvalue` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keyorigin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_type_id` int unsigned DEFAULT NULL,
  `protected` int DEFAULT NULL,
  PRIMARY KEY (`image_id`),
  KEY `images_image_type_id_foreign` (`image_type_id`),
  CONSTRAINT `images_image_type_id_foreign` FOREIGN KEY (`image_type_id`) REFERENCES `image_types` (`image_type_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `images`
--

LOCK TABLES `images` WRITE;
/*!40000 ALTER TABLE `images` DISABLE KEYS */;
INSERT INTO `images` VALUES (1,119537515,'HN64T9BE',291829,'3LXU7CLS','Folder',2,0),(2,119537375,'9HE8NJ8Q',291829,'J97R5S9Z','Subject',1,1);
/*!40000 ALTER TABLE `images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `issue_categories`
--

DROP TABLE IF EXISTS `issue_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `issue_categories` (
  `issue_category_id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`issue_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `issue_categories`
--

LOCK TABLES `issue_categories` WRITE;
/*!40000 ALTER TABLE `issue_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `issue_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `issues`
--

DROP TABLE IF EXISTS `issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `issues` (
  `issue_id` int unsigned NOT NULL AUTO_INCREMENT,
  `issue_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issue_description` text COLLATE utf8mb4_unicode_ci,
  `issue_error_message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approval_status_id` int unsigned DEFAULT NULL,
  `issue_category_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`issue_id`),
  KEY `issues_approval_status_id_foreign` (`approval_status_id`),
  KEY `issues_issue_category_id_foreign` (`issue_category_id`),
  CONSTRAINT `issues_approval_status_id_foreign` FOREIGN KEY (`approval_status_id`) REFERENCES `status` (`status_id`) ON DELETE CASCADE,
  CONSTRAINT `issues_issue_category_id_foreign` FOREIGN KEY (`issue_category_id`) REFERENCES `issue_categories` (`issue_category_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `issues`
--

LOCK TABLES `issues` WRITE;
/*!40000 ALTER TABLE `issues` DISABLE KEYS */;
/*!40000 ALTER TABLE `issues` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `job_id` int unsigned NOT NULL AUTO_INCREMENT,
  `ts_season_id` int DEFAULT NULL,
  `ts_account_id` int DEFAULT NULL,
  `ts_job_id` int DEFAULT NULL,
  `ts_jobkey` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ts_jobname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ts_schoolkey` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jobsync_status_id` int unsigned DEFAULT NULL,
  `foldersync_status_id` int unsigned DEFAULT NULL,
  `job_status_id` int unsigned DEFAULT NULL,
  `proof_start` datetime DEFAULT NULL,
  `proof_warning` datetime DEFAULT NULL,
  `proof_due` datetime DEFAULT NULL,
  `force_sync` int DEFAULT NULL,
  PRIMARY KEY (`job_id`),
  KEY `jobs_jobsync_status_id_foreign` (`jobsync_status_id`),
  KEY `jobs_foldersync_status_id_foreign` (`foldersync_status_id`),
  KEY `jobs_job_status_id_foreign` (`job_status_id`),
  CONSTRAINT `jobs_foldersync_status_id_foreign` FOREIGN KEY (`foldersync_status_id`) REFERENCES `status` (`status_id`) ON DELETE CASCADE,
  CONSTRAINT `jobs_job_status_id_foreign` FOREIGN KEY (`job_status_id`) REFERENCES `status` (`status_id`) ON DELETE CASCADE,
  CONSTRAINT `jobs_jobsync_status_id_foreign` FOREIGN KEY (`jobsync_status_id`) REFERENCES `status` (`status_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
INSERT INTO `jobs` VALUES (1,25,1,291829,'9VZ8QLWE','Apex Scholars High School','ANO',3,3,1,'2024-05-21 11:04:56','2024-06-02 12:04:56','2024-06-15 10:04:56',0);
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2024_04_18_102236_create_status_table',1),(2,'2024_04_22_122132_create_errors_table',2),(3,'2024_07_01_170749_create_roles_table',3),(4,'2024_07_01_170919_create_permissions_table',4),(5,'2024_07_01_171022_create_role_has_permissions_table',5),(6,'2024_07_01_171350_create_users_table',6),(7,'2024_07_02_090450_create_user_roles_table',7),(8,'2024_07_02_090608_create_franchises_table',8),(9,'2024_07_02_090825_create_franchise_users_table',9),(10,'2024_07_02_090949_create_schools_table',10),(11,'2024_07_02_091329_create_school_users_table',11),(12,'2024_07_02_091447_create_school_franchises_table',12),(13,'2024_07_02_091702_create_schooldetails_table',13),(14,'2024_07_02_103421_create_seasons_table',14),(15,'2024_07_02_103520_create_jobs_table',15),(16,'2024_07_02_103521_create_sync_changelogs_table',16),(17,'2024_07_02_104947_create_folder_tags_table',17),(18,'2024_07_02_104948_create_folders_table',18),(19,'2024_07_02_131836_create_subjects_table',19),(20,'2024_07_02_132040_create_image_types_table',20),(21,'2024_07_02_132116_create_images_table',21),(22,'2024_07_02_132441_create_email_categories_table',22),(23,'2024_07_02_132532_create_templates_table',23),(24,'2024_07_02_132651_create_emails_table',24),(25,'2024_07_02_133323_create_issue_categories_table',25),(26,'2024_07_02_133331_create_issues_table',26),(27,'2024_07_02_133342_create_changelogs_table',27),(28,'2024_07_02_133404_create_group_positions_table',28);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `permission_id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `role_permission_id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int unsigned DEFAULT NULL,
  `permission_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`role_permission_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  KEY `role_has_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `role_id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `school_franchises`
--

DROP TABLE IF EXISTS `school_franchises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `school_franchises` (
  `school_franchise_id` int unsigned NOT NULL AUTO_INCREMENT,
  `franchise_id` int unsigned DEFAULT NULL,
  `school_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`school_franchise_id`),
  KEY `school_franchises_franchise_id_foreign` (`franchise_id`),
  KEY `school_franchises_school_id_foreign` (`school_id`),
  CONSTRAINT `school_franchises_franchise_id_foreign` FOREIGN KEY (`franchise_id`) REFERENCES `franchises` (`franchise_id`) ON DELETE CASCADE,
  CONSTRAINT `school_franchises_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `school_franchises`
--

LOCK TABLES `school_franchises` WRITE;
/*!40000 ALTER TABLE `school_franchises` DISABLE KEYS */;
INSERT INTO `school_franchises` VALUES (1,1,1);
/*!40000 ALTER TABLE `school_franchises` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `school_users`
--

DROP TABLE IF EXISTS `school_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `school_users` (
  `school_user_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `school_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`school_user_id`),
  KEY `school_users_user_id_foreign` (`user_id`),
  KEY `school_users_school_id_foreign` (`school_id`),
  CONSTRAINT `school_users_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  CONSTRAINT `school_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `school_users`
--

LOCK TABLES `school_users` WRITE;
/*!40000 ALTER TABLE `school_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `school_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schooldetails`
--

DROP TABLE IF EXISTS `schooldetails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schooldetails` (
  `schooldetail_id` int unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int unsigned DEFAULT NULL,
  `photoday` datetime DEFAULT NULL,
  `catchup_date` datetime DEFAULT NULL,
  `digitalDownload_date` datetime DEFAULT NULL,
  `principal` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ts_season_id` int DEFAULT NULL,
  PRIMARY KEY (`schooldetail_id`),
  KEY `schooldetails_school_id_foreign` (`school_id`),
  CONSTRAINT `schooldetails_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schooldetails`
--

LOCK TABLES `schooldetails` WRITE;
/*!40000 ALTER TABLE `schooldetails` DISABLE KEYS */;
/*!40000 ALTER TABLE `schooldetails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schools`
--

DROP TABLE IF EXISTS `schools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schools` (
  `school_id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `schoolkey` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `postcode` int DEFAULT NULL,
  `suburb` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_id` int DEFAULT NULL,
  PRIMARY KEY (`school_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schools`
--

LOCK TABLES `schools` WRITE;
/*!40000 ALTER TABLE `schools` DISABLE KEYS */;
INSERT INTO `schools` VALUES (1,'Apex Scholars School',NULL,NULL,'NTY',NULL,870,'ALICE SPRINGS','NT','Australia',1);
/*!40000 ALTER TABLE `schools` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seasons`
--

DROP TABLE IF EXISTS `seasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `seasons` (
  `season_id` int unsigned NOT NULL AUTO_INCREMENT,
  `ts_season_id` int DEFAULT NULL,
  `code` int DEFAULT NULL,
  `is_default` int DEFAULT NULL,
  PRIMARY KEY (`season_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seasons`
--

LOCK TABLES `seasons` WRITE;
/*!40000 ALTER TABLE `seasons` DISABLE KEYS */;
INSERT INTO `seasons` VALUES (1,1,2010,0),(2,2,2011,0),(3,3,2012,0),(4,4,2013,0),(5,5,2014,0),(6,6,2015,0),(7,7,2016,0),(8,8,2000,0),(9,9,2001,0),(10,10,2002,0),(11,11,2003,0),(12,12,2004,0),(13,13,2005,0),(14,14,2006,0),(15,15,2007,0),(16,16,2008,0),(17,17,2009,0),(18,19,2017,0),(19,20,2018,0),(20,21,2019,0),(21,22,2020,0),(22,23,2021,0),(23,24,2022,0),(24,25,2023,1);
/*!40000 ALTER TABLE `seasons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `status`
--

DROP TABLE IF EXISTS `status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `status` (
  `status_id` int unsigned NOT NULL AUTO_INCREMENT,
  `status_internal_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_external_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `colour_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`status_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `status`
--

LOCK TABLES `status` WRITE;
/*!40000 ALTER TABLE `status` DISABLE KEYS */;
INSERT INTO `status` VALUES (1,'Active','Active','#007bff'),(2,'Inactive','Inactive','#6c757d'),(3,'Sync','Sync','#6c757d'),(4,'Unsync','Unsync','#6c757d'),(5,'Review','Review',NULL),(6,'Success','Success',NULL),(7,'Duplicate','Duplicate',NULL),(8,'Error','Error',NULL),(9,'Pending','Pending',NULL),(10,'None','None',NULL);
/*!40000 ALTER TABLE `status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subjects` (
  `subject_id` int unsigned NOT NULL AUTO_INCREMENT,
  `firstname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salutation` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ts_subjectkey` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ts_job_id` int DEFAULT NULL,
  `ts_folder_id` int DEFAULT NULL,
  PRIMARY KEY (`subject_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects`
--

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
INSERT INTO `subjects` VALUES (1,'Bryan','Baxter',NULL,NULL,'J97R5S9Z',291829,7167332);
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sync_changelogs`
--

DROP TABLE IF EXISTS `sync_changelogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sync_changelogs` (
  `sync_changelog_id` int unsigned NOT NULL AUTO_INCREMENT,
  `details` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sync_changelog_id`),
  KEY `sync_changelogs_job_id_foreign` (`job_id`),
  CONSTRAINT `sync_changelogs_job_id_foreign` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sync_changelogs`
--

LOCK TABLES `sync_changelogs` WRITE;
/*!40000 ALTER TABLE `sync_changelogs` DISABLE KEYS */;
/*!40000 ALTER TABLE `sync_changelogs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sync_errors`
--

DROP TABLE IF EXISTS `sync_errors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sync_errors` (
  `error_id` int unsigned NOT NULL AUTO_INCREMENT,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `error_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_fromtable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_totable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `blueprintJobId` int DEFAULT NULL,
  `status_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`error_id`),
  KEY `errors_status_id_foreign` (`status_id`),
  CONSTRAINT `errors_status_id_foreign` FOREIGN KEY (`status_id`) REFERENCES `status` (`status_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sync_errors`
--

LOCK TABLES `sync_errors` WRITE;
/*!40000 ALTER TABLE `sync_errors` DISABLE KEYS */;
/*!40000 ALTER TABLE `sync_errors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `templates`
--

DROP TABLE IF EXISTS `templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `templates` (
  `template_id` int unsigned NOT NULL AUTO_INCREMENT,
  `template_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `template_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `template_format` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `templates`
--

LOCK TABLES `templates` WRITE;
/*!40000 ALTER TABLE `templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_roles` (
  `user_role_id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int unsigned DEFAULT NULL,
  `user_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`user_role_id`),
  KEY `user_roles_role_id_foreign` (`role_id`),
  KEY `user_roles_user_id_foreign` (`user_id`),
  CONSTRAINT `user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_roles`
--

LOCK TABLES `user_roles` WRITE;
/*!40000 ALTER TABLE `user_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int unsigned NOT NULL AUTO_INCREMENT,
  `firstname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `suburb` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postcode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active_status_id` int unsigned DEFAULT NULL,
  `activation_date` datetime DEFAULT NULL,
  `expiry_date` datetime DEFAULT NULL,
  `password_expiry` int DEFAULT NULL,
  `password_expiry_date` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  KEY `users_active_status_id_foreign` (`active_status_id`),
  CONSTRAINT `users_active_status_id_foreign` FOREIGN KEY (`active_status_id`) REFERENCES `status` (`status_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-07-08 13:20:29
