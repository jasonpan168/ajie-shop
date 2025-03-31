-- MySQL dump 10.13  Distrib 5.7.44, for Linux (x86_64)
--
-- Host: localhost    Database: cess
-- ------------------------------------------------------
-- Server version	5.7.44-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auto_card_tasks`
--

DROP TABLE IF EXISTS `auto_card_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auto_card_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `status` enum('active','completed') DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `repeat_flag` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auto_card_tasks`
--

LOCK TABLES `auto_card_tasks` WRITE;
/*!40000 ALTER TABLE `auto_card_tasks` DISABLE KEYS */;
INSERT INTO `auto_card_tasks` VALUES (15,13,'自动发卡测试2','active','2025-03-30 19:53:58',1);
/*!40000 ALTER TABLE `auto_card_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auto_cards`
--

DROP TABLE IF EXISTS `auto_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auto_cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `card_content` text NOT NULL,
  `status` enum('unused','used') DEFAULT 'unused',
  `email` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `order_no` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  CONSTRAINT `auto_cards_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `auto_card_tasks` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auto_cards`
--

LOCK TABLES `auto_cards` WRITE;
/*!40000 ALTER TABLE `auto_cards` DISABLE KEYS */;
INSERT INTO `auto_cards` VALUES (26,15,'11111111111111111111111','unused','','2025-03-30 19:53:58','');
/*!40000 ALTER TABLE `auto_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL COMMENT '优惠码',
  `discount_amount` decimal(10,2) NOT NULL COMMENT '优惠金额',
  `status` enum('active','used','expired') NOT NULL DEFAULT 'active' COMMENT '状态：active-可用，used-已使用，expired-已过期',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `used_at` timestamp NULL DEFAULT NULL COMMENT '使用时间',
  `used_order_no` varchar(50) DEFAULT NULL COMMENT '使用的订单号',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
INSERT INTO `coupons` VALUES (4,'VIPEEA52D29',1.00,'active','2025-03-29 01:04:27',NULL,NULL),(5,'VIP6892CA50',1.00,'active','2025-03-29 01:04:27',NULL,NULL),(6,'VIP90E2A576',1.00,'active','2025-03-29 01:04:27',NULL,NULL);
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_settings`
--

DROP TABLE IF EXISTS `email_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `smtp_host` varchar(255) NOT NULL,
  `smtp_port` int(11) NOT NULL,
  `smtp_user` varchar(255) NOT NULL,
  `smtp_pass` varchar(255) NOT NULL,
  `sender_name` varchar(255) NOT NULL,
  `sender_email` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `channel_type` varchar(20) DEFAULT 'smtp' COMMENT '发信通道类型',
  `is_default` tinyint(1) DEFAULT '1' COMMENT '是否为默认通道',
  PRIMARY KEY (`id`),
  KEY `idx_channel_type` (`channel_type`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_settings`
--

LOCK TABLES `email_settings` WRITE;
/*!40000 ALTER TABLE `email_settings` DISABLE KEYS */;
INSERT INTO `email_settings` VALUES (1,'smtp.mailgun.org',465,'XXX@163.com','shouquanma','阿杰商城','XXX@163.com','2025-02-15 06:59:41','2025-03-31 03:35:21','smtp',1);
/*!40000 ALTER TABLE `email_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `epay_config`
--

DROP TABLE IF EXISTS `epay_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `epay_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apiurl` varchar(255) NOT NULL,
  `pid` varchar(50) NOT NULL,
  `key` varchar(255) NOT NULL,
  `notify_url` varchar(255) NOT NULL,
  `return_url` varchar(255) NOT NULL,
  `alipay_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `wxpay_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `usdt_enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `epay_config`
--

LOCK TABLES `epay_config` WRITE;
/*!40000 ALTER TABLE `epay_config` DISABLE KEYS */;
INSERT INTO `epay_config` VALUES (1,'你的域名','1','1','https://你的域名/notify_url.php','https://你的域名/return_url.php',0,0,0);
/*!40000 ALTER TABLE `epay_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ip_limits`
--

DROP TABLE IF EXISTS `ip_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ip_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `last_attempt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `attempt_count` int(11) NOT NULL DEFAULT '1',
  `is_blocked` tinyint(1) NOT NULL DEFAULT '0',
  `permanently_unlocked` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ip` (`ip`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ip_limits`
--

LOCK TABLES `ip_limits` WRITE;
/*!40000 ALTER TABLE `ip_limits` DISABLE KEYS */;
INSERT INTO `ip_limits` VALUES (44,'113.76.11.136','2025-03-30 03:17:41',1,0,0,'2025-03-30 03:17:41','2025-03-30 03:17:41'),(63,'95.135.181.49','2025-03-31 01:31:20',1,0,0,'2025-03-31 01:31:20','2025-03-31 01:31:20');
/*!40000 ALTER TABLE `ip_limits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menus`
--

LOCK TABLES `menus` WRITE;
/*!40000 ALTER TABLE `menus` DISABLE KEYS */;
INSERT INTO `menus` VALUES (1,'订阅油管','https://www.youtube.com/@ajieshuo?sub_confirmation=1',0),(2,'电报交流群','https://t.me/+yK7diUyqmxI2MjZl',0);
/*!40000 ALTER TABLE `menus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(50) NOT NULL,
  `nickname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_title` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT '1',
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','cancelled') DEFAULT 'pending',
  `pay_type` varchar(20) NOT NULL DEFAULT '',
  `coupon_id` int(11) DEFAULT NULL COMMENT '使用的优惠码ID',
  `coupon_code` varchar(50) DEFAULT NULL COMMENT '使用的优惠码',
  `coupon_amount` decimal(10,2) DEFAULT '0.00' COMMENT '优惠码抵扣金额',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `card_sent` tinyint(1) NOT NULL DEFAULT '0',
  `ip` varchar(45) DEFAULT NULL COMMENT 'IP地址',
  PRIMARY KEY (`id`),
  KEY `idx_ip_created` (`ip`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=258 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `detail` text,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT '0',
  `cover` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT '0',
  `is_autocard` tinyint(1) NOT NULL DEFAULT '0',
  `status` int(11) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'这是商品','这是商品','测试商品',1.00,990,'https://cdn.laikr.com//shujuku/202503231143317.png',0,0,1),(5,'测试下架','测试','',1.00,100,'https://cdn.laikr.com//shujuku/202503231143317.png',8,0,0),(9,'这是商品','商品','测 his',1.00,100,'https://cdn.laikr.com//shujuku/202503231143317.png',1,0,1),(13,'这是商品','测试','',1.00,1107,'https://cdn.laikr.com//shujuku/202503231143317.png',0,1,1);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_config`
--

DROP TABLE IF EXISTS `system_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(50) NOT NULL COMMENT '配置键名',
  `value` text COMMENT '配置值',
  `description` varchar(255) DEFAULT NULL COMMENT '配置描述',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_config`
--

LOCK TABLES `system_config` WRITE;
/*!40000 ALTER TABLE `system_config` DISABLE KEYS */;
INSERT INTO `system_config` VALUES (1,'coupon_enabled','1','是否启用优惠码功能：1-启用，0-禁用');
/*!40000 ALTER TABLE `system_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES ('wxpusher_admin_uid','去绑定','2025-03-31 03:36:00','2025-03-31 03:36:00'),('wxpusher_app_token','去获取','2025-03-31 03:36:00','2025-03-31 03:36:00'),('wxpusher_enabled','0','2025-03-31 03:36:00','2025-03-31 03:36:00'),('wxpusher_order_notify','0','2025-03-31 03:36:00','2025-03-31 03:36:00'),('wxpusher_payment_notify','0','2025-03-31 03:36:00','2025-03-31 03:36:00');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `telegram_config`
--

DROP TABLE IF EXISTS `telegram_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `telegram_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bot_token` varchar(100) NOT NULL,
  `chat_id` varchar(20) NOT NULL,
  `enabled` tinyint(1) DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `telegram_config`
--

LOCK TABLES `telegram_config` WRITE;
/*!40000 ALTER TABLE `telegram_config` DISABLE KEYS */;
INSERT INTO `telegram_config` VALUES (1,'参考文档','电报 ID',0,'2025-03-31 03:35:42');
/*!40000 ALTER TABLE `telegram_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wechat_config`
--

DROP TABLE IF EXISTS `wechat_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wechat_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `appid` varchar(64) NOT NULL,
  `mch_id` varchar(64) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `notify_url` varchar(255) NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wechat_config`
--

LOCK TABLES `wechat_config` WRITE;
/*!40000 ALTER TABLE `wechat_config` DISABLE KEYS */;
INSERT INTO `wechat_config` VALUES (1,'1','1','1','https:// 你的域名/notify.php','2025-03-31 03:33:34',1);
/*!40000 ALTER TABLE `wechat_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'cess'
--

--
-- Dumping routines for database 'cess'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-03-31 11:49:06
