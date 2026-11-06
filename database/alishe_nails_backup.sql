-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: alishe_nails
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'Store Admin','admin@alishenails.com','$2y$12$yfVsaBJLocqwKi3VabQIzO.ZKTUKLPtPnGOlbynJ9jzoJTCPfirJm',NULL,'2026-11-03 15:24:36','2026-11-03 15:24:36');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('alishe_nails_cache356a192b7913b04c54574d18c28d46e6395428ab','i:1;',1793978732),('alishe_nails_cache356a192b7913b04c54574d18c28d46e6395428ab:timer','i:1793978732;',1793978732),('alishe_nails_cache5c785c036466adea360111aa28563bfd556b5fba','i:1;',1793978676),('alishe_nails_cache5c785c036466adea360111aa28563bfd556b5fba:timer','i:1793978676;',1793978676),('alishe_nails_cachehome_collections_and_bestsellers','a:2:{i:0;O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:4:{i:0;O:19:\"App\\Models\\Category\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:10:\"categories\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:1;s:4:\"name\";s:17:\"Everyday Elegance\";s:4:\"slug\";s:17:\"everyday-elegance\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:14:\"products_count\";i:4;}s:11:\"\0*\0original\";a:6:{s:2:\"id\";i:1;s:4:\"name\";s:17:\"Everyday Elegance\";s:4:\"slug\";s:17:\"everyday-elegance\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:14:\"products_count\";i:4;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:2:{i:0;s:4:\"name\";i:1;s:4:\"slug\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:1;O:19:\"App\\Models\\Category\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:10:\"categories\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:2;s:4:\"name\";s:9:\"Soft Glam\";s:4:\"slug\";s:9:\"soft-glam\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:14:\"products_count\";i:2;}s:11:\"\0*\0original\";a:6:{s:2:\"id\";i:2;s:4:\"name\";s:9:\"Soft Glam\";s:4:\"slug\";s:9:\"soft-glam\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:14:\"products_count\";i:2;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:2:{i:0;s:4:\"name\";i:1;s:4:\"slug\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:2;O:19:\"App\\Models\\Category\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:10:\"categories\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:3;s:4:\"name\";s:11:\"Bold & Chic\";s:4:\"slug\";s:9:\"bold-chic\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:14:\"products_count\";i:3;}s:11:\"\0*\0original\";a:6:{s:2:\"id\";i:3;s:4:\"name\";s:11:\"Bold & Chic\";s:4:\"slug\";s:9:\"bold-chic\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:14:\"products_count\";i:3;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:2:{i:0;s:4:\"name\";i:1;s:4:\"slug\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:3;O:19:\"App\\Models\\Category\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:10:\"categories\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:4;s:4:\"name\";s:14:\"Luxury Edition\";s:4:\"slug\";s:14:\"luxury-edition\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:14:\"products_count\";i:3;}s:11:\"\0*\0original\";a:6:{s:2:\"id\";i:4;s:4:\"name\";s:14:\"Luxury Edition\";s:4:\"slug\";s:14:\"luxury-edition\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:14:\"products_count\";i:3;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:2:{i:0;s:4:\"name\";i:1;s:4:\"slug\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}i:1;O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:4:{i:0;O:18:\"App\\Models\\Product\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:8:\"products\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:26:{s:2:\"id\";i:1;s:11:\"category_id\";i:2;s:9:\"seller_id\";N;s:4:\"name\";s:17:\"Rosy Quartz Ombre\";s:4:\"slug\";s:17:\"rosy-quartz-ombre\";s:3:\"sku\";s:10:\"ALN-HJI0BC\";s:5:\"price\";s:7:\"2500.00\";s:16:\"compare_at_price\";N;s:17:\"short_description\";s:51:\"Hand-painted ombre with delicate gold leaf accents.\";s:11:\"description\";s:163:\"Hand-painted elegance with a seamless gradient and delicate gold leaf accents. Crafted for a salon-quality finish at home. Reusable up to 5 times with proper care.\";s:5:\"shape\";s:6:\"Almond\";s:6:\"length\";s:6:\"Medium\";s:6:\"finish\";s:5:\"Matte\";s:5:\"image\";s:21:\"rosy-quartz-ombre.jpg\";s:7:\"gallery\";s:113:\"[\"rosy-quartz-ombre.jpg\",\"rosy-quartz-ombre-worn.jpg\",\"rosy-quartz-ombre-detail.jpg\",\"rosy-quartz-ombre-kit.jpg\"]\";s:5:\"media\";N;s:5:\"badge\";s:3:\"NEW\";s:14:\"is_best_seller\";i:1;s:11:\"is_featured\";i:1;s:5:\"stock\";i:10;s:9:\"is_active\";i:1;s:14:\"whats_included\";s:162:\"[\"10 Hand-painted press-on nails\",\"1 Mini nail file & buffer\",\"1 Wooden cuticle pusher\",\"24 Adhesive sticky tabs\",\"1 Liquid nail glue (2g)\",\"2 Alcohol prep pads\"]\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-06 19:21:25\";s:22:\"approved_reviews_count\";i:3;s:27:\"approved_reviews_avg_rating\";s:6:\"4.6667\";}s:11:\"\0*\0original\";a:26:{s:2:\"id\";i:1;s:11:\"category_id\";i:2;s:9:\"seller_id\";N;s:4:\"name\";s:17:\"Rosy Quartz Ombre\";s:4:\"slug\";s:17:\"rosy-quartz-ombre\";s:3:\"sku\";s:10:\"ALN-HJI0BC\";s:5:\"price\";s:7:\"2500.00\";s:16:\"compare_at_price\";N;s:17:\"short_description\";s:51:\"Hand-painted ombre with delicate gold leaf accents.\";s:11:\"description\";s:163:\"Hand-painted elegance with a seamless gradient and delicate gold leaf accents. Crafted for a salon-quality finish at home. Reusable up to 5 times with proper care.\";s:5:\"shape\";s:6:\"Almond\";s:6:\"length\";s:6:\"Medium\";s:6:\"finish\";s:5:\"Matte\";s:5:\"image\";s:21:\"rosy-quartz-ombre.jpg\";s:7:\"gallery\";s:113:\"[\"rosy-quartz-ombre.jpg\",\"rosy-quartz-ombre-worn.jpg\",\"rosy-quartz-ombre-detail.jpg\",\"rosy-quartz-ombre-kit.jpg\"]\";s:5:\"media\";N;s:5:\"badge\";s:3:\"NEW\";s:14:\"is_best_seller\";i:1;s:11:\"is_featured\";i:1;s:5:\"stock\";i:10;s:9:\"is_active\";i:1;s:14:\"whats_included\";s:162:\"[\"10 Hand-painted press-on nails\",\"1 Mini nail file & buffer\",\"1 Wooden cuticle pusher\",\"24 Adhesive sticky tabs\",\"1 Liquid nail glue (2g)\",\"2 Alcohol prep pads\"]\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-06 19:21:25\";s:22:\"approved_reviews_count\";i:3;s:27:\"approved_reviews_avg_rating\";s:6:\"4.6667\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:8:{s:7:\"gallery\";s:5:\"array\";s:5:\"media\";s:5:\"array\";s:14:\"whats_included\";s:5:\"array\";s:5:\"price\";s:9:\"decimal:2\";s:16:\"compare_at_price\";s:9:\"decimal:2\";s:14:\"is_best_seller\";s:7:\"boolean\";s:11:\"is_featured\";s:7:\"boolean\";s:9:\"is_active\";s:7:\"boolean\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:1:{s:6:\"seller\";N;}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:21:{i:0;s:11:\"category_id\";i:1;s:9:\"seller_id\";i:2;s:4:\"name\";i:3;s:4:\"slug\";i:4;s:3:\"sku\";i:5;s:5:\"price\";i:6;s:16:\"compare_at_price\";i:7;s:17:\"short_description\";i:8;s:11:\"description\";i:9;s:5:\"shape\";i:10;s:6:\"length\";i:11;s:6:\"finish\";i:12;s:5:\"image\";i:13;s:7:\"gallery\";i:14;s:5:\"media\";i:15;s:5:\"badge\";i:16;s:14:\"is_best_seller\";i:17;s:11:\"is_featured\";i:18;s:5:\"stock\";i:19;s:9:\"is_active\";i:20;s:14:\"whats_included\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:1;O:18:\"App\\Models\\Product\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:8:\"products\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:26:{s:2:\"id\";i:3;s:11:\"category_id\";i:1;s:9:\"seller_id\";N;s:4:\"name\";s:19:\"Classic Pearl Mauve\";s:4:\"slug\";s:19:\"classic-pearl-mauve\";s:3:\"sku\";s:10:\"ALN-RMYIUP\";s:5:\"price\";s:7:\"2200.00\";s:16:\"compare_at_price\";N;s:17:\"short_description\";s:46:\"A timeless mauve shade, perfect for every day.\";s:11:\"description\";s:75:\"Our signature everyday mauve — soft, pearlescent, and endlessly wearable.\";s:5:\"shape\";s:6:\"Almond\";s:6:\"length\";s:6:\"Medium\";s:6:\"finish\";s:5:\"Matte\";s:5:\"image\";s:23:\"classic-pearl-mauve.jpg\";s:7:\"gallery\";s:27:\"[\"classic-pearl-mauve.jpg\"]\";s:5:\"media\";N;s:5:\"badge\";s:11:\"BEST SELLER\";s:14:\"is_best_seller\";i:1;s:11:\"is_featured\";i:1;s:5:\"stock\";i:51;s:9:\"is_active\";i:1;s:14:\"whats_included\";s:162:\"[\"10 Hand-painted press-on nails\",\"1 Mini nail file & buffer\",\"1 Wooden cuticle pusher\",\"24 Adhesive sticky tabs\",\"1 Liquid nail glue (2g)\",\"2 Alcohol prep pads\"]\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:22:\"approved_reviews_count\";i:3;s:27:\"approved_reviews_avg_rating\";s:6:\"4.6667\";}s:11:\"\0*\0original\";a:26:{s:2:\"id\";i:3;s:11:\"category_id\";i:1;s:9:\"seller_id\";N;s:4:\"name\";s:19:\"Classic Pearl Mauve\";s:4:\"slug\";s:19:\"classic-pearl-mauve\";s:3:\"sku\";s:10:\"ALN-RMYIUP\";s:5:\"price\";s:7:\"2200.00\";s:16:\"compare_at_price\";N;s:17:\"short_description\";s:46:\"A timeless mauve shade, perfect for every day.\";s:11:\"description\";s:75:\"Our signature everyday mauve — soft, pearlescent, and endlessly wearable.\";s:5:\"shape\";s:6:\"Almond\";s:6:\"length\";s:6:\"Medium\";s:6:\"finish\";s:5:\"Matte\";s:5:\"image\";s:23:\"classic-pearl-mauve.jpg\";s:7:\"gallery\";s:27:\"[\"classic-pearl-mauve.jpg\"]\";s:5:\"media\";N;s:5:\"badge\";s:11:\"BEST SELLER\";s:14:\"is_best_seller\";i:1;s:11:\"is_featured\";i:1;s:5:\"stock\";i:51;s:9:\"is_active\";i:1;s:14:\"whats_included\";s:162:\"[\"10 Hand-painted press-on nails\",\"1 Mini nail file & buffer\",\"1 Wooden cuticle pusher\",\"24 Adhesive sticky tabs\",\"1 Liquid nail glue (2g)\",\"2 Alcohol prep pads\"]\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:22:\"approved_reviews_count\";i:3;s:27:\"approved_reviews_avg_rating\";s:6:\"4.6667\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:8:{s:7:\"gallery\";s:5:\"array\";s:5:\"media\";s:5:\"array\";s:14:\"whats_included\";s:5:\"array\";s:5:\"price\";s:9:\"decimal:2\";s:16:\"compare_at_price\";s:9:\"decimal:2\";s:14:\"is_best_seller\";s:7:\"boolean\";s:11:\"is_featured\";s:7:\"boolean\";s:9:\"is_active\";s:7:\"boolean\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:1:{s:6:\"seller\";N;}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:21:{i:0;s:11:\"category_id\";i:1;s:9:\"seller_id\";i:2;s:4:\"name\";i:3;s:4:\"slug\";i:4;s:3:\"sku\";i:5;s:5:\"price\";i:6;s:16:\"compare_at_price\";i:7;s:17:\"short_description\";i:8;s:11:\"description\";i:9;s:5:\"shape\";i:10;s:6:\"length\";i:11;s:6:\"finish\";i:12;s:5:\"image\";i:13;s:7:\"gallery\";i:14;s:5:\"media\";i:15;s:5:\"badge\";i:16;s:14:\"is_best_seller\";i:17;s:11:\"is_featured\";i:18;s:5:\"stock\";i:19;s:9:\"is_active\";i:20;s:14:\"whats_included\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:2;O:18:\"App\\Models\\Product\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:8:\"products\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:26:{s:2:\"id\";i:10;s:11:\"category_id\";i:4;s:9:\"seller_id\";N;s:4:\"name\";s:13:\"Golden French\";s:4:\"slug\";s:13:\"golden-french\";s:3:\"sku\";s:10:\"ALN-P5BII7\";s:5:\"price\";s:7:\"4500.00\";s:16:\"compare_at_price\";N;s:17:\"short_description\";s:44:\"French tip reimagined with a soft gold line.\";s:11:\"description\";s:75:\"A modern French manicure finished with a fine soft-gold line along the tip.\";s:5:\"shape\";s:6:\"Almond\";s:6:\"length\";s:6:\"Medium\";s:6:\"finish\";s:6:\"Glossy\";s:5:\"image\";s:17:\"golden-french.jpg\";s:7:\"gallery\";s:21:\"[\"golden-french.jpg\"]\";s:5:\"media\";N;s:5:\"badge\";s:11:\"BEST SELLER\";s:14:\"is_best_seller\";i:1;s:11:\"is_featured\";i:0;s:5:\"stock\";i:43;s:9:\"is_active\";i:1;s:14:\"whats_included\";s:162:\"[\"10 Hand-painted press-on nails\",\"1 Mini nail file & buffer\",\"1 Wooden cuticle pusher\",\"24 Adhesive sticky tabs\",\"1 Liquid nail glue (2g)\",\"2 Alcohol prep pads\"]\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:22:\"approved_reviews_count\";i:3;s:27:\"approved_reviews_avg_rating\";s:6:\"4.6667\";}s:11:\"\0*\0original\";a:26:{s:2:\"id\";i:10;s:11:\"category_id\";i:4;s:9:\"seller_id\";N;s:4:\"name\";s:13:\"Golden French\";s:4:\"slug\";s:13:\"golden-french\";s:3:\"sku\";s:10:\"ALN-P5BII7\";s:5:\"price\";s:7:\"4500.00\";s:16:\"compare_at_price\";N;s:17:\"short_description\";s:44:\"French tip reimagined with a soft gold line.\";s:11:\"description\";s:75:\"A modern French manicure finished with a fine soft-gold line along the tip.\";s:5:\"shape\";s:6:\"Almond\";s:6:\"length\";s:6:\"Medium\";s:6:\"finish\";s:6:\"Glossy\";s:5:\"image\";s:17:\"golden-french.jpg\";s:7:\"gallery\";s:21:\"[\"golden-french.jpg\"]\";s:5:\"media\";N;s:5:\"badge\";s:11:\"BEST SELLER\";s:14:\"is_best_seller\";i:1;s:11:\"is_featured\";i:0;s:5:\"stock\";i:43;s:9:\"is_active\";i:1;s:14:\"whats_included\";s:162:\"[\"10 Hand-painted press-on nails\",\"1 Mini nail file & buffer\",\"1 Wooden cuticle pusher\",\"24 Adhesive sticky tabs\",\"1 Liquid nail glue (2g)\",\"2 Alcohol prep pads\"]\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:22:\"approved_reviews_count\";i:3;s:27:\"approved_reviews_avg_rating\";s:6:\"4.6667\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:8:{s:7:\"gallery\";s:5:\"array\";s:5:\"media\";s:5:\"array\";s:14:\"whats_included\";s:5:\"array\";s:5:\"price\";s:9:\"decimal:2\";s:16:\"compare_at_price\";s:9:\"decimal:2\";s:14:\"is_best_seller\";s:7:\"boolean\";s:11:\"is_featured\";s:7:\"boolean\";s:9:\"is_active\";s:7:\"boolean\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:1:{s:6:\"seller\";N;}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:21:{i:0;s:11:\"category_id\";i:1;s:9:\"seller_id\";i:2;s:4:\"name\";i:3;s:4:\"slug\";i:4;s:3:\"sku\";i:5;s:5:\"price\";i:6;s:16:\"compare_at_price\";i:7;s:17:\"short_description\";i:8;s:11:\"description\";i:9;s:5:\"shape\";i:10;s:6:\"length\";i:11;s:6:\"finish\";i:12;s:5:\"image\";i:13;s:7:\"gallery\";i:14;s:5:\"media\";i:15;s:5:\"badge\";i:16;s:14:\"is_best_seller\";i:17;s:11:\"is_featured\";i:18;s:5:\"stock\";i:19;s:9:\"is_active\";i:20;s:14:\"whats_included\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:3;O:18:\"App\\Models\\Product\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:8:\"products\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:26:{s:2:\"id\";i:11;s:11:\"category_id\";i:1;s:9:\"seller_id\";N;s:4:\"name\";s:10:\"Matte Rose\";s:4:\"slug\";s:10:\"matte-rose\";s:3:\"sku\";s:10:\"ALN-WVQR0Y\";s:5:\"price\";s:7:\"3800.00\";s:16:\"compare_at_price\";N;s:17:\"short_description\";s:46:\"A muted matte rose, coffin shape, long length.\";s:11:\"description\";s:52:\"A muted matte rose shade in a sculpted coffin shape.\";s:5:\"shape\";s:6:\"Coffin\";s:6:\"length\";s:4:\"Long\";s:6:\"finish\";s:5:\"Matte\";s:5:\"image\";s:14:\"matte-rose.jpg\";s:7:\"gallery\";s:18:\"[\"matte-rose.jpg\"]\";s:5:\"media\";N;s:5:\"badge\";N;s:14:\"is_best_seller\";i:1;s:11:\"is_featured\";i:0;s:5:\"stock\";i:33;s:9:\"is_active\";i:1;s:14:\"whats_included\";s:162:\"[\"10 Hand-painted press-on nails\",\"1 Mini nail file & buffer\",\"1 Wooden cuticle pusher\",\"24 Adhesive sticky tabs\",\"1 Liquid nail glue (2g)\",\"2 Alcohol prep pads\"]\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:22:\"approved_reviews_count\";i:3;s:27:\"approved_reviews_avg_rating\";s:6:\"4.6667\";}s:11:\"\0*\0original\";a:26:{s:2:\"id\";i:11;s:11:\"category_id\";i:1;s:9:\"seller_id\";N;s:4:\"name\";s:10:\"Matte Rose\";s:4:\"slug\";s:10:\"matte-rose\";s:3:\"sku\";s:10:\"ALN-WVQR0Y\";s:5:\"price\";s:7:\"3800.00\";s:16:\"compare_at_price\";N;s:17:\"short_description\";s:46:\"A muted matte rose, coffin shape, long length.\";s:11:\"description\";s:52:\"A muted matte rose shade in a sculpted coffin shape.\";s:5:\"shape\";s:6:\"Coffin\";s:6:\"length\";s:4:\"Long\";s:6:\"finish\";s:5:\"Matte\";s:5:\"image\";s:14:\"matte-rose.jpg\";s:7:\"gallery\";s:18:\"[\"matte-rose.jpg\"]\";s:5:\"media\";N;s:5:\"badge\";N;s:14:\"is_best_seller\";i:1;s:11:\"is_featured\";i:0;s:5:\"stock\";i:33;s:9:\"is_active\";i:1;s:14:\"whats_included\";s:162:\"[\"10 Hand-painted press-on nails\",\"1 Mini nail file & buffer\",\"1 Wooden cuticle pusher\",\"24 Adhesive sticky tabs\",\"1 Liquid nail glue (2g)\",\"2 Alcohol prep pads\"]\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:22:\"approved_reviews_count\";i:3;s:27:\"approved_reviews_avg_rating\";s:6:\"4.6667\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:8:{s:7:\"gallery\";s:5:\"array\";s:5:\"media\";s:5:\"array\";s:14:\"whats_included\";s:5:\"array\";s:5:\"price\";s:9:\"decimal:2\";s:16:\"compare_at_price\";s:9:\"decimal:2\";s:14:\"is_best_seller\";s:7:\"boolean\";s:11:\"is_featured\";s:7:\"boolean\";s:9:\"is_active\";s:7:\"boolean\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:1:{s:6:\"seller\";N;}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:21:{i:0;s:11:\"category_id\";i:1;s:9:\"seller_id\";i:2;s:4:\"name\";i:3;s:4:\"slug\";i:4;s:3:\"sku\";i:5;s:5:\"price\";i:6;s:16:\"compare_at_price\";i:7;s:17:\"short_description\";i:8;s:11:\"description\";i:9;s:5:\"shape\";i:10;s:6:\"length\";i:11;s:6:\"finish\";i:12;s:5:\"image\";i:13;s:7:\"gallery\";i:14;s:5:\"media\";i:15;s:5:\"badge\";i:16;s:14:\"is_best_seller\";i:17;s:11:\"is_featured\";i:18;s:5:\"stock\";i:19;s:9:\"is_active\";i:20;s:14:\"whats_included\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}}',1793978916),('alishe_nails_cachereload:127.0.0.1:/','i:1;',1793978749),('alishe_nails_cachereload:127.0.0.1:/:timer','i:1793978749;',1793978749),('alishe_nails_cachereload:127.0.0.1:/account/orders','i:1;',1793978696),('alishe_nails_cachereload:127.0.0.1:/account/orders:timer','i:1793978696;',1793978696),('alishe_nails_cachereload:127.0.0.1:/account/profile','i:1;',1793978690),('alishe_nails_cachereload:127.0.0.1:/account/profile:timer','i:1793978690;',1793978690),('alishe_nails_cachereload:127.0.0.1:/account/wishlist','i:1;',1793978704),('alishe_nails_cachereload:127.0.0.1:/account/wishlist:timer','i:1793978704;',1793978704),('alishe_nails_cachereload:127.0.0.1:/cart','i:1;',1793978708),('alishe_nails_cachereload:127.0.0.1:/cart:timer','i:1793978708;',1793978708),('alishe_nails_cachereload:127.0.0.1:/checkout','i:1;',1793978732),('alishe_nails_cachereload:127.0.0.1:/checkout:timer','i:1793978732;',1793978732),('alishe_nails_cachereload:127.0.0.1:/login','i:1;',1793978658),('alishe_nails_cachereload:127.0.0.1:/login:timer','i:1793978658;',1793978658),('alishe_nails_cachereload:127.0.0.1:/products/midnight-espresso','i:2;',1793978715),('alishe_nails_cachereload:127.0.0.1:/products/midnight-espresso:timer','i:1793978715;',1793978715),('alishe_nails_cachereload:127.0.0.1:/shop','i:1;',1793978711),('alishe_nails_cachereload:127.0.0.1:/shop:timer','i:1793978711;',1793978711),('alishe_nails_cacheshop_sidebar_facets','a:4:{i:0;O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:4:{i:0;O:19:\"App\\Models\\Category\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:10:\"categories\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:1;s:4:\"name\";s:17:\"Everyday Elegance\";s:4:\"slug\";s:17:\"everyday-elegance\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:14:\"products_count\";i:4;}s:11:\"\0*\0original\";a:6:{s:2:\"id\";i:1;s:4:\"name\";s:17:\"Everyday Elegance\";s:4:\"slug\";s:17:\"everyday-elegance\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:14:\"products_count\";i:4;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:2:{i:0;s:4:\"name\";i:1;s:4:\"slug\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:1;O:19:\"App\\Models\\Category\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:10:\"categories\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:2;s:4:\"name\";s:9:\"Soft Glam\";s:4:\"slug\";s:9:\"soft-glam\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:14:\"products_count\";i:2;}s:11:\"\0*\0original\";a:6:{s:2:\"id\";i:2;s:4:\"name\";s:9:\"Soft Glam\";s:4:\"slug\";s:9:\"soft-glam\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:14:\"products_count\";i:2;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:2:{i:0;s:4:\"name\";i:1;s:4:\"slug\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:2;O:19:\"App\\Models\\Category\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:10:\"categories\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:3;s:4:\"name\";s:11:\"Bold & Chic\";s:4:\"slug\";s:9:\"bold-chic\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:14:\"products_count\";i:3;}s:11:\"\0*\0original\";a:6:{s:2:\"id\";i:3;s:4:\"name\";s:11:\"Bold & Chic\";s:4:\"slug\";s:9:\"bold-chic\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:14:\"products_count\";i:3;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:2:{i:0;s:4:\"name\";i:1;s:4:\"slug\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:3;O:19:\"App\\Models\\Category\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:10:\"categories\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:6:{s:2:\"id\";i:4;s:4:\"name\";s:14:\"Luxury Edition\";s:4:\"slug\";s:14:\"luxury-edition\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:14:\"products_count\";i:3;}s:11:\"\0*\0original\";a:6:{s:2:\"id\";i:4;s:4:\"name\";s:14:\"Luxury Edition\";s:4:\"slug\";s:14:\"luxury-edition\";s:10:\"created_at\";s:19:\"2026-11-03 20:24:37\";s:10:\"updated_at\";s:19:\"2026-11-03 20:24:37\";s:14:\"products_count\";i:3;}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:0:{}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:2:{i:0;s:4:\"name\";i:1;s:4:\"slug\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}i:1;O:29:\"Illuminate\\Support\\Collection\":2:{s:8:\"\0*\0items\";a:4:{s:6:\"Almond\";i:6;s:6:\"Coffin\";i:3;s:6:\"Square\";i:2;s:8:\"Stiletto\";i:1;}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}i:2;O:29:\"Illuminate\\Support\\Collection\":2:{s:8:\"\0*\0items\";a:3:{s:4:\"Long\";i:4;s:6:\"Medium\";i:7;s:5:\"Short\";i:1;}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}i:3;O:29:\"Illuminate\\Support\\Collection\":2:{s:8:\"\0*\0items\";a:3:{s:14:\"Glitter/Chrome\";i:1;s:6:\"Glossy\";i:5;s:5:\"Matte\";i:6;}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}}',1793978951);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaign_pitches`
--

DROP TABLE IF EXISTS `campaign_pitches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaign_pitches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `handle` varchar(150) NOT NULL,
  `follower_count` int(10) unsigned NOT NULL,
  `campaign_type` varchar(50) NOT NULL,
  `budget_range` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `portfolio_links` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_pitches_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaign_pitches`
--

LOCK TABLES `campaign_pitches` WRITE;
/*!40000 ALTER TABLE `campaign_pitches` DISABLE KEYS */;
/*!40000 ALTER TABLE `campaign_pitches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Everyday Elegance','everyday-elegance','2026-11-03 15:24:37','2026-11-03 15:24:37'),(2,'Soft Glam','soft-glam','2026-11-03 15:24:37','2026-11-03 15:24:37'),(3,'Bold & Chic','bold-chic','2026-11-03 15:24:37','2026-11-03 15:24:37'),(4,'Luxury Edition','luxury-edition','2026-11-03 15:24:37','2026-11-03 15:24:37');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `type` enum('fixed','percent') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `usage_limit` int(10) unsigned DEFAULT NULL,
  `used_count` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupons_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
INSERT INTO `coupons` VALUES (1,'WELCOME10','percent',10.00,'2027-11-03 15:37:22',500,1,1,'2026-11-03 15:24:39','2026-11-06 04:00:51'),(2,'ALISHE500','fixed',500.00,'2027-11-03 15:37:22',200,0,1,'2026-11-03 15:24:39','2026-11-03 15:37:22'),(3,'GLAM20','percent',20.00,'2027-05-03 15:37:22',100,0,1,'2026-11-03 15:24:39','2026-11-03 15:37:22');
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `instagram_posts`
--

DROP TABLE IF EXISTS `instagram_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `instagram_posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `instagram_id` varchar(255) NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'IMAGE',
  `media_url` text NOT NULL,
  `thumbnail_url` text DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `permalink` varchar(255) NOT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instagram_posts_instagram_id_unique` (`instagram_id`),
  KEY `instagram_posts_posted_at_index` (`posted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `instagram_posts`
--

LOCK TABLES `instagram_posts` WRITE;
/*!40000 ALTER TABLE `instagram_posts` DISABLE KEYS */;
INSERT INTO `instagram_posts` VALUES (1,'post_1788693396','IMAGE','/images/products/rosy-quartz-ombre.jpg',NULL,'Handcrafted perfection ✨ Rosy Quartz Ombre with genuine gold leaf detailing. Reusable up to 5 times. #AlisheNails #PressOnNails','https://www.instagram.com/alishe_nails/','2026-11-02 15:37:22',1,'2026-11-03 15:24:38','2026-11-03 15:37:22'),(2,'post_1788694839','IMAGE','/images/products/midnight-espresso.jpg',NULL,'Moody, elegant, bold. Midnight Espresso with metallic gold rim in sculpted coffin shape. 🤎 #NailsOfInstagram #LuxuryPressOns','https://www.instagram.com/alishe_nails/','2026-11-01 15:37:22',1,'2026-11-03 15:24:38','2026-11-03 15:37:22'),(3,'post_1788700748','IMAGE','/images/products/golden-french.jpg',NULL,'Elevate your everyday manicure with our Golden French collection. High-gloss salon finish at home. ✨ #FrenchNails #AlisheNails','https://www.instagram.com/alishe_nails/','2026-10-31 15:37:22',1,'2026-11-03 15:24:39','2026-11-03 15:37:22'),(4,'post_1788701221','IMAGE','/images/products/ivory-blossom-vine.jpg',NULL,'Hand-painted miniature botanical vines over velvety ivory white base. Pure romantic artistry. 🌿🤍 #CustomPressOns #NailArt','https://www.instagram.com/alishe_nails/','2026-10-30 15:37:22',1,'2026-11-03 15:24:39','2026-11-03 15:37:22'),(5,'post_1788695851','IMAGE','/images/products/blush-aura.jpg',NULL,'Dreamy blush aura gradient with a velvety matte topcoat. Available in Almond & Coffin. 🌸 #AuraNails #MatteNails','https://www.instagram.com/alishe_nails/','2026-10-29 15:37:22',1,'2026-11-03 15:24:39','2026-11-03 15:37:22'),(6,'post_1788693359','IMAGE','/images/products/pink-marble.jpg',NULL,'Rose quartz marble texture with delicate gold foil veins. Handmade with love for your special occasions. 💅✨','https://www.instagram.com/alishe_nails/','2026-10-28 15:37:22',1,'2026-11-03 15:24:39','2026-11-03 15:37:22'),(7,'post_1788693955','IMAGE','/images/products/gilded-rose-dust.jpg',NULL,'Glitter and chrome that catches the light from every single angle. Sparkle in style with Gilded Rose Dust. 💖','https://www.instagram.com/alishe_nails/','2026-10-27 15:37:22',1,'2026-11-03 15:24:39','2026-11-03 15:37:22'),(8,'post_1788694706','IMAGE','/images/products/modern-french-matte.jpg',NULL,'A clean modern take on the classic French manicure in soft matte mauve. Chic, timeless, effortless. #AlisheNails','https://www.instagram.com/alishe_nails/','2026-10-26 15:37:22',1,'2026-11-03 15:24:39','2026-11-03 15:37:22');
/*!40000 ALTER TABLE `instagram_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
INSERT INTO `jobs` VALUES (1,'default','{\"uuid\":\"30749bc2-53d3-45ba-9b6c-b28c93353e8c\",\"displayName\":\"App\\\\Jobs\\\\SendOrderEmails\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendOrderEmails\",\"command\":\"O:24:\\\"App\\\\Jobs\\\\SendOrderEmails\\\":2:{s:5:\\\"order\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:16:\\\"App\\\\Models\\\\Order\\\";s:2:\\\"id\\\";i:11;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:9:\\\"lineItems\\\";a:1:{i:0;O:20:\\\"App\\\\Models\\\\OrderItem\\\":33:{s:13:\\\"\\u0000*\\u0000connection\\\";s:5:\\\"mysql\\\";s:8:\\\"\\u0000*\\u0000table\\\";s:11:\\\"order_items\\\";s:13:\\\"\\u0000*\\u0000primaryKey\\\";s:2:\\\"id\\\";s:10:\\\"\\u0000*\\u0000keyType\\\";s:3:\\\"int\\\";s:12:\\\"incrementing\\\";b:1;s:7:\\\"\\u0000*\\u0000with\\\";a:0:{}s:12:\\\"\\u0000*\\u0000withCount\\\";a:0:{}s:19:\\\"preventsLazyLoading\\\";b:0;s:10:\\\"\\u0000*\\u0000perPage\\\";i:15;s:6:\\\"exists\\\";b:1;s:18:\\\"wasRecentlyCreated\\\";b:1;s:28:\\\"\\u0000*\\u0000escapeWhenCastingToString\\\";b:0;s:13:\\\"\\u0000*\\u0000attributes\\\";a:15:{s:10:\\\"product_id\\\";i:1;s:12:\\\"product_name\\\";s:17:\\\"Rosy Quartz Ombre\\\";s:5:\\\"shape\\\";s:13:\\\"Almond Medium\\\";s:4:\\\"size\\\";s:1:\\\"S\\\";s:8:\\\"quantity\\\";i:1;s:5:\\\"price\\\";s:7:\\\"2500.00\\\";s:10:\\\"line_total\\\";d:2500;s:9:\\\"seller_id\\\";N;s:15:\\\"commission_rate\\\";d:0;s:17:\\\"commission_amount\\\";d:0;s:14:\\\"seller_earning\\\";d:0;s:8:\\\"order_id\\\";i:11;s:10:\\\"updated_at\\\";s:19:\\\"2026-11-04 21:11:58\\\";s:10:\\\"created_at\\\";s:19:\\\"2026-11-04 21:11:58\\\";s:2:\\\"id\\\";i:22;}s:11:\\\"\\u0000*\\u0000original\\\";a:15:{s:10:\\\"product_id\\\";i:1;s:12:\\\"product_name\\\";s:17:\\\"Rosy Quartz Ombre\\\";s:5:\\\"shape\\\";s:13:\\\"Almond Medium\\\";s:4:\\\"size\\\";s:1:\\\"S\\\";s:8:\\\"quantity\\\";i:1;s:5:\\\"price\\\";s:7:\\\"2500.00\\\";s:10:\\\"line_total\\\";d:2500;s:9:\\\"seller_id\\\";N;s:15:\\\"commission_rate\\\";d:0;s:17:\\\"commission_amount\\\";d:0;s:14:\\\"seller_earning\\\";d:0;s:8:\\\"order_id\\\";i:11;s:10:\\\"updated_at\\\";s:19:\\\"2026-11-04 21:11:58\\\";s:10:\\\"created_at\\\";s:19:\\\"2026-11-04 21:11:58\\\";s:2:\\\"id\\\";i:22;}s:10:\\\"\\u0000*\\u0000changes\\\";a:0:{}s:11:\\\"\\u0000*\\u0000previous\\\";a:0:{}s:8:\\\"\\u0000*\\u0000casts\\\";a:5:{s:5:\\\"price\\\";s:9:\\\"decimal:2\\\";s:10:\\\"line_total\\\";s:9:\\\"decimal:2\\\";s:15:\\\"commission_rate\\\";s:9:\\\"decimal:2\\\";s:17:\\\"commission_amount\\\";s:9:\\\"decimal:2\\\";s:14:\\\"seller_earning\\\";s:9:\\\"decimal:2\\\";}s:17:\\\"\\u0000*\\u0000classCastCache\\\";a:0:{}s:21:\\\"\\u0000*\\u0000attributeCastCache\\\";a:0:{}s:13:\\\"\\u0000*\\u0000dateFormat\\\";N;s:10:\\\"\\u0000*\\u0000appends\\\";a:0:{}s:19:\\\"\\u0000*\\u0000dispatchesEvents\\\";a:0:{}s:14:\\\"\\u0000*\\u0000observables\\\";a:0:{}s:12:\\\"\\u0000*\\u0000relations\\\";a:0:{}s:10:\\\"\\u0000*\\u0000touches\\\";a:0:{}s:27:\\\"\\u0000*\\u0000relationAutoloadCallback\\\";N;s:26:\\\"\\u0000*\\u0000relationAutoloadContext\\\";N;s:10:\\\"timestamps\\\";b:1;s:13:\\\"usesUniqueIds\\\";b:0;s:9:\\\"\\u0000*\\u0000hidden\\\";a:0:{}s:10:\\\"\\u0000*\\u0000visible\\\";a:0:{}s:11:\\\"\\u0000*\\u0000fillable\\\";a:13:{i:0;s:8:\\\"order_id\\\";i:1;s:10:\\\"product_id\\\";i:2;s:9:\\\"seller_id\\\";i:3;s:12:\\\"product_name\\\";i:4;s:5:\\\"shape\\\";i:5;s:4:\\\"size\\\";i:6;s:8:\\\"quantity\\\";i:7;s:5:\\\"price\\\";i:8;s:10:\\\"line_total\\\";i:9;s:15:\\\"commission_rate\\\";i:10;s:17:\\\"commission_amount\\\";i:11;s:14:\\\"seller_earning\\\";i:12;s:13:\\\"payout_status\\\";}s:10:\\\"\\u0000*\\u0000guarded\\\";a:1:{i:0;s:1:\\\"*\\\";}}}}\",\"batchId\":null},\"createdAt\":1793808720,\"delay\":null}',0,NULL,1793808720,1793808720),(2,'default','{\"uuid\":\"9ebe856a-e808-425c-a073-079b51c69d5a\",\"displayName\":\"App\\\\Jobs\\\\SendOrderEmails\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendOrderEmails\",\"command\":\"O:24:\\\"App\\\\Jobs\\\\SendOrderEmails\\\":2:{s:5:\\\"order\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:16:\\\"App\\\\Models\\\\Order\\\";s:2:\\\"id\\\";i:12;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:9:\\\"lineItems\\\";a:1:{i:0;O:20:\\\"App\\\\Models\\\\OrderItem\\\":33:{s:13:\\\"\\u0000*\\u0000connection\\\";s:5:\\\"mysql\\\";s:8:\\\"\\u0000*\\u0000table\\\";s:11:\\\"order_items\\\";s:13:\\\"\\u0000*\\u0000primaryKey\\\";s:2:\\\"id\\\";s:10:\\\"\\u0000*\\u0000keyType\\\";s:3:\\\"int\\\";s:12:\\\"incrementing\\\";b:1;s:7:\\\"\\u0000*\\u0000with\\\";a:0:{}s:12:\\\"\\u0000*\\u0000withCount\\\";a:0:{}s:19:\\\"preventsLazyLoading\\\";b:0;s:10:\\\"\\u0000*\\u0000perPage\\\";i:15;s:6:\\\"exists\\\";b:1;s:18:\\\"wasRecentlyCreated\\\";b:1;s:28:\\\"\\u0000*\\u0000escapeWhenCastingToString\\\";b:0;s:13:\\\"\\u0000*\\u0000attributes\\\";a:15:{s:10:\\\"product_id\\\";i:1;s:12:\\\"product_name\\\";s:17:\\\"Rosy Quartz Ombre\\\";s:5:\\\"shape\\\";N;s:4:\\\"size\\\";N;s:8:\\\"quantity\\\";s:1:\\\"2\\\";s:5:\\\"price\\\";s:7:\\\"2500.00\\\";s:10:\\\"line_total\\\";d:5000;s:9:\\\"seller_id\\\";N;s:15:\\\"commission_rate\\\";d:0;s:17:\\\"commission_amount\\\";d:0;s:14:\\\"seller_earning\\\";d:0;s:8:\\\"order_id\\\";i:12;s:10:\\\"updated_at\\\";s:19:\\\"2026-11-06 09:00:51\\\";s:10:\\\"created_at\\\";s:19:\\\"2026-11-06 09:00:51\\\";s:2:\\\"id\\\";i:23;}s:11:\\\"\\u0000*\\u0000original\\\";a:15:{s:10:\\\"product_id\\\";i:1;s:12:\\\"product_name\\\";s:17:\\\"Rosy Quartz Ombre\\\";s:5:\\\"shape\\\";N;s:4:\\\"size\\\";N;s:8:\\\"quantity\\\";s:1:\\\"2\\\";s:5:\\\"price\\\";s:7:\\\"2500.00\\\";s:10:\\\"line_total\\\";d:5000;s:9:\\\"seller_id\\\";N;s:15:\\\"commission_rate\\\";d:0;s:17:\\\"commission_amount\\\";d:0;s:14:\\\"seller_earning\\\";d:0;s:8:\\\"order_id\\\";i:12;s:10:\\\"updated_at\\\";s:19:\\\"2026-11-06 09:00:51\\\";s:10:\\\"created_at\\\";s:19:\\\"2026-11-06 09:00:51\\\";s:2:\\\"id\\\";i:23;}s:10:\\\"\\u0000*\\u0000changes\\\";a:0:{}s:11:\\\"\\u0000*\\u0000previous\\\";a:0:{}s:8:\\\"\\u0000*\\u0000casts\\\";a:5:{s:5:\\\"price\\\";s:9:\\\"decimal:2\\\";s:10:\\\"line_total\\\";s:9:\\\"decimal:2\\\";s:15:\\\"commission_rate\\\";s:9:\\\"decimal:2\\\";s:17:\\\"commission_amount\\\";s:9:\\\"decimal:2\\\";s:14:\\\"seller_earning\\\";s:9:\\\"decimal:2\\\";}s:17:\\\"\\u0000*\\u0000classCastCache\\\";a:0:{}s:21:\\\"\\u0000*\\u0000attributeCastCache\\\";a:0:{}s:13:\\\"\\u0000*\\u0000dateFormat\\\";N;s:10:\\\"\\u0000*\\u0000appends\\\";a:0:{}s:19:\\\"\\u0000*\\u0000dispatchesEvents\\\";a:0:{}s:14:\\\"\\u0000*\\u0000observables\\\";a:0:{}s:12:\\\"\\u0000*\\u0000relations\\\";a:0:{}s:10:\\\"\\u0000*\\u0000touches\\\";a:0:{}s:27:\\\"\\u0000*\\u0000relationAutoloadCallback\\\";N;s:26:\\\"\\u0000*\\u0000relationAutoloadContext\\\";N;s:10:\\\"timestamps\\\";b:1;s:13:\\\"usesUniqueIds\\\";b:0;s:9:\\\"\\u0000*\\u0000hidden\\\";a:0:{}s:10:\\\"\\u0000*\\u0000visible\\\";a:0:{}s:11:\\\"\\u0000*\\u0000fillable\\\";a:13:{i:0;s:8:\\\"order_id\\\";i:1;s:10:\\\"product_id\\\";i:2;s:9:\\\"seller_id\\\";i:3;s:12:\\\"product_name\\\";i:4;s:5:\\\"shape\\\";i:5;s:4:\\\"size\\\";i:6;s:8:\\\"quantity\\\";i:7;s:5:\\\"price\\\";i:8;s:10:\\\"line_total\\\";i:9;s:15:\\\"commission_rate\\\";i:10;s:17:\\\"commission_amount\\\";i:11;s:14:\\\"seller_earning\\\";i:12;s:13:\\\"payout_status\\\";}s:10:\\\"\\u0000*\\u0000guarded\\\";a:1:{i:0;s:1:\\\"*\\\";}}}}\",\"batchId\":null},\"createdAt\":1793937652,\"delay\":null}',0,NULL,1793937652,1793937652),(3,'default','{\"uuid\":\"47b90843-e0ef-42b7-8164-ac0015fb1aca\",\"displayName\":\"App\\\\Jobs\\\\SendOrderEmails\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendOrderEmails\",\"command\":\"O:24:\\\"App\\\\Jobs\\\\SendOrderEmails\\\":2:{s:5:\\\"order\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:16:\\\"App\\\\Models\\\\Order\\\";s:2:\\\"id\\\";i:13;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:9:\\\"lineItems\\\";a:1:{i:0;O:20:\\\"App\\\\Models\\\\OrderItem\\\":33:{s:13:\\\"\\u0000*\\u0000connection\\\";s:5:\\\"mysql\\\";s:8:\\\"\\u0000*\\u0000table\\\";s:11:\\\"order_items\\\";s:13:\\\"\\u0000*\\u0000primaryKey\\\";s:2:\\\"id\\\";s:10:\\\"\\u0000*\\u0000keyType\\\";s:3:\\\"int\\\";s:12:\\\"incrementing\\\";b:1;s:7:\\\"\\u0000*\\u0000with\\\";a:0:{}s:12:\\\"\\u0000*\\u0000withCount\\\";a:0:{}s:19:\\\"preventsLazyLoading\\\";b:0;s:10:\\\"\\u0000*\\u0000perPage\\\";i:15;s:6:\\\"exists\\\";b:1;s:18:\\\"wasRecentlyCreated\\\";b:1;s:28:\\\"\\u0000*\\u0000escapeWhenCastingToString\\\";b:0;s:13:\\\"\\u0000*\\u0000attributes\\\";a:15:{s:10:\\\"product_id\\\";i:1;s:12:\\\"product_name\\\";s:17:\\\"Rosy Quartz Ombre\\\";s:5:\\\"shape\\\";s:13:\\\"Almond Medium\\\";s:4:\\\"size\\\";s:1:\\\"S\\\";s:8:\\\"quantity\\\";i:1;s:5:\\\"price\\\";s:7:\\\"2500.00\\\";s:10:\\\"line_total\\\";d:2500;s:9:\\\"seller_id\\\";N;s:15:\\\"commission_rate\\\";d:0;s:17:\\\"commission_amount\\\";d:0;s:14:\\\"seller_earning\\\";d:0;s:8:\\\"order_id\\\";i:13;s:10:\\\"updated_at\\\";s:19:\\\"2026-11-06 19:21:00\\\";s:10:\\\"created_at\\\";s:19:\\\"2026-11-06 19:21:00\\\";s:2:\\\"id\\\";i:24;}s:11:\\\"\\u0000*\\u0000original\\\";a:15:{s:10:\\\"product_id\\\";i:1;s:12:\\\"product_name\\\";s:17:\\\"Rosy Quartz Ombre\\\";s:5:\\\"shape\\\";s:13:\\\"Almond Medium\\\";s:4:\\\"size\\\";s:1:\\\"S\\\";s:8:\\\"quantity\\\";i:1;s:5:\\\"price\\\";s:7:\\\"2500.00\\\";s:10:\\\"line_total\\\";d:2500;s:9:\\\"seller_id\\\";N;s:15:\\\"commission_rate\\\";d:0;s:17:\\\"commission_amount\\\";d:0;s:14:\\\"seller_earning\\\";d:0;s:8:\\\"order_id\\\";i:13;s:10:\\\"updated_at\\\";s:19:\\\"2026-11-06 19:21:00\\\";s:10:\\\"created_at\\\";s:19:\\\"2026-11-06 19:21:00\\\";s:2:\\\"id\\\";i:24;}s:10:\\\"\\u0000*\\u0000changes\\\";a:0:{}s:11:\\\"\\u0000*\\u0000previous\\\";a:0:{}s:8:\\\"\\u0000*\\u0000casts\\\";a:5:{s:5:\\\"price\\\";s:9:\\\"decimal:2\\\";s:10:\\\"line_total\\\";s:9:\\\"decimal:2\\\";s:15:\\\"commission_rate\\\";s:9:\\\"decimal:2\\\";s:17:\\\"commission_amount\\\";s:9:\\\"decimal:2\\\";s:14:\\\"seller_earning\\\";s:9:\\\"decimal:2\\\";}s:17:\\\"\\u0000*\\u0000classCastCache\\\";a:0:{}s:21:\\\"\\u0000*\\u0000attributeCastCache\\\";a:0:{}s:13:\\\"\\u0000*\\u0000dateFormat\\\";N;s:10:\\\"\\u0000*\\u0000appends\\\";a:0:{}s:19:\\\"\\u0000*\\u0000dispatchesEvents\\\";a:0:{}s:14:\\\"\\u0000*\\u0000observables\\\";a:0:{}s:12:\\\"\\u0000*\\u0000relations\\\";a:0:{}s:10:\\\"\\u0000*\\u0000touches\\\";a:0:{}s:27:\\\"\\u0000*\\u0000relationAutoloadCallback\\\";N;s:26:\\\"\\u0000*\\u0000relationAutoloadContext\\\";N;s:10:\\\"timestamps\\\";b:1;s:13:\\\"usesUniqueIds\\\";b:0;s:9:\\\"\\u0000*\\u0000hidden\\\";a:0:{}s:10:\\\"\\u0000*\\u0000visible\\\";a:0:{}s:11:\\\"\\u0000*\\u0000fillable\\\";a:13:{i:0;s:8:\\\"order_id\\\";i:1;s:10:\\\"product_id\\\";i:2;s:9:\\\"seller_id\\\";i:3;s:12:\\\"product_name\\\";i:4;s:5:\\\"shape\\\";i:5;s:4:\\\"size\\\";i:6;s:8:\\\"quantity\\\";i:7;s:5:\\\"price\\\";i:8;s:10:\\\"line_total\\\";i:9;s:15:\\\"commission_rate\\\";i:10;s:17:\\\"commission_amount\\\";i:11;s:14:\\\"seller_earning\\\";i:12;s:13:\\\"payout_status\\\";}s:10:\\\"\\u0000*\\u0000guarded\\\";a:1:{i:0;s:1:\\\"*\\\";}}}}\",\"batchId\":null},\"createdAt\":1793974864,\"delay\":null}',0,NULL,1793974864,1793974864),(4,'default','{\"uuid\":\"57e64f6b-a0bd-4126-a398-25c766146d2f\",\"displayName\":\"App\\\\Jobs\\\\SendOrderEmails\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":\"30\",\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendOrderEmails\",\"command\":\"O:24:\\\"App\\\\Jobs\\\\SendOrderEmails\\\":2:{s:5:\\\"order\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:16:\\\"App\\\\Models\\\\Order\\\";s:2:\\\"id\\\";i:14;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:9:\\\"lineItems\\\";a:1:{i:0;O:20:\\\"App\\\\Models\\\\OrderItem\\\":33:{s:13:\\\"\\u0000*\\u0000connection\\\";s:5:\\\"mysql\\\";s:8:\\\"\\u0000*\\u0000table\\\";s:11:\\\"order_items\\\";s:13:\\\"\\u0000*\\u0000primaryKey\\\";s:2:\\\"id\\\";s:10:\\\"\\u0000*\\u0000keyType\\\";s:3:\\\"int\\\";s:12:\\\"incrementing\\\";b:1;s:7:\\\"\\u0000*\\u0000with\\\";a:0:{}s:12:\\\"\\u0000*\\u0000withCount\\\";a:0:{}s:19:\\\"preventsLazyLoading\\\";b:0;s:10:\\\"\\u0000*\\u0000perPage\\\";i:15;s:6:\\\"exists\\\";b:1;s:18:\\\"wasRecentlyCreated\\\";b:1;s:28:\\\"\\u0000*\\u0000escapeWhenCastingToString\\\";b:0;s:13:\\\"\\u0000*\\u0000attributes\\\";a:15:{s:10:\\\"product_id\\\";i:1;s:12:\\\"product_name\\\";s:17:\\\"Rosy Quartz Ombre\\\";s:5:\\\"shape\\\";s:13:\\\"Almond Medium\\\";s:4:\\\"size\\\";s:1:\\\"S\\\";s:8:\\\"quantity\\\";i:1;s:5:\\\"price\\\";s:7:\\\"2500.00\\\";s:10:\\\"line_total\\\";d:2500;s:9:\\\"seller_id\\\";N;s:15:\\\"commission_rate\\\";d:0;s:17:\\\"commission_amount\\\";d:0;s:14:\\\"seller_earning\\\";d:0;s:8:\\\"order_id\\\";i:14;s:10:\\\"updated_at\\\";s:19:\\\"2026-11-06 19:21:25\\\";s:10:\\\"created_at\\\";s:19:\\\"2026-11-06 19:21:25\\\";s:2:\\\"id\\\";i:25;}s:11:\\\"\\u0000*\\u0000original\\\";a:15:{s:10:\\\"product_id\\\";i:1;s:12:\\\"product_name\\\";s:17:\\\"Rosy Quartz Ombre\\\";s:5:\\\"shape\\\";s:13:\\\"Almond Medium\\\";s:4:\\\"size\\\";s:1:\\\"S\\\";s:8:\\\"quantity\\\";i:1;s:5:\\\"price\\\";s:7:\\\"2500.00\\\";s:10:\\\"line_total\\\";d:2500;s:9:\\\"seller_id\\\";N;s:15:\\\"commission_rate\\\";d:0;s:17:\\\"commission_amount\\\";d:0;s:14:\\\"seller_earning\\\";d:0;s:8:\\\"order_id\\\";i:14;s:10:\\\"updated_at\\\";s:19:\\\"2026-11-06 19:21:25\\\";s:10:\\\"created_at\\\";s:19:\\\"2026-11-06 19:21:25\\\";s:2:\\\"id\\\";i:25;}s:10:\\\"\\u0000*\\u0000changes\\\";a:0:{}s:11:\\\"\\u0000*\\u0000previous\\\";a:0:{}s:8:\\\"\\u0000*\\u0000casts\\\";a:5:{s:5:\\\"price\\\";s:9:\\\"decimal:2\\\";s:10:\\\"line_total\\\";s:9:\\\"decimal:2\\\";s:15:\\\"commission_rate\\\";s:9:\\\"decimal:2\\\";s:17:\\\"commission_amount\\\";s:9:\\\"decimal:2\\\";s:14:\\\"seller_earning\\\";s:9:\\\"decimal:2\\\";}s:17:\\\"\\u0000*\\u0000classCastCache\\\";a:0:{}s:21:\\\"\\u0000*\\u0000attributeCastCache\\\";a:0:{}s:13:\\\"\\u0000*\\u0000dateFormat\\\";N;s:10:\\\"\\u0000*\\u0000appends\\\";a:0:{}s:19:\\\"\\u0000*\\u0000dispatchesEvents\\\";a:0:{}s:14:\\\"\\u0000*\\u0000observables\\\";a:0:{}s:12:\\\"\\u0000*\\u0000relations\\\";a:0:{}s:10:\\\"\\u0000*\\u0000touches\\\";a:0:{}s:27:\\\"\\u0000*\\u0000relationAutoloadCallback\\\";N;s:26:\\\"\\u0000*\\u0000relationAutoloadContext\\\";N;s:10:\\\"timestamps\\\";b:1;s:13:\\\"usesUniqueIds\\\";b:0;s:9:\\\"\\u0000*\\u0000hidden\\\";a:0:{}s:10:\\\"\\u0000*\\u0000visible\\\";a:0:{}s:11:\\\"\\u0000*\\u0000fillable\\\";a:13:{i:0;s:8:\\\"order_id\\\";i:1;s:10:\\\"product_id\\\";i:2;s:9:\\\"seller_id\\\";i:3;s:12:\\\"product_name\\\";i:4;s:5:\\\"shape\\\";i:5;s:4:\\\"size\\\";i:6;s:8:\\\"quantity\\\";i:7;s:5:\\\"price\\\";i:8;s:10:\\\"line_total\\\";i:9;s:15:\\\"commission_rate\\\";i:10;s:17:\\\"commission_amount\\\";i:11;s:14:\\\"seller_earning\\\";i:12;s:13:\\\"payout_status\\\";}s:10:\\\"\\u0000*\\u0000guarded\\\";a:1:{i:0;s:1:\\\"*\\\";}}}}\",\"batchId\":null},\"createdAt\":1793974885,\"delay\":null}',0,NULL,1793974885,1793974885);
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marketplace_settings`
--

DROP TABLE IF EXISTS `marketplace_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `marketplace_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `marketplace_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marketplace_settings`
--

LOCK TABLES `marketplace_settings` WRITE;
/*!40000 ALTER TABLE `marketplace_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `marketplace_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2024_01_01_000001_create_categories_table',1),(5,'2024_01_01_000002_create_products_table',1),(6,'2024_01_01_000003_create_reviews_table',1),(7,'2024_01_01_000004_create_orders_table',1),(8,'2024_01_01_000005_create_order_items_table',1),(9,'2024_02_01_000000_add_phone_to_users_table',1),(10,'2024_02_01_000001_create_admins_table',1),(11,'2024_02_01_000002_add_user_and_payment_status_to_orders_table',1),(12,'2024_02_01_000003_add_moderation_to_reviews_table',1),(13,'2024_02_01_000004_add_is_active_to_products_table',1),(14,'2024_02_01_000005_create_newsletter_subscribers_table',1),(15,'2024_02_01_000006_create_shipping_rates_and_add_area_to_orders_table',1),(16,'2026_09_06_000000_add_stock_restored_at_to_orders_table',1),(17,'2026_09_07_000001_create_sellers_table',1),(18,'2026_09_07_000001_create_wishlists_table',1),(19,'2026_09_07_000002_add_marketplace_fields_to_products',1),(20,'2026_09_07_000002_create_coupons_table',1),(21,'2026_09_07_000003_add_coupon_fields_to_orders_table',1),(22,'2026_09_07_000003_add_marketplace_fields_to_order_items',1),(23,'2026_09_07_000004_create_marketplace_settings_table',1),(24,'2026_09_07_000005_create_instagram_posts_table',1),(25,'2026_09_07_000006_create_subscription_plans_table',1),(26,'2026_09_07_000007_create_seller_subscriptions_table',1),(27,'2026_09_07_100000_create_campaign_pitches_table',1),(28,'2026_09_07_200000_add_media_to_products_table',1),(29,'2026_10_09_000001_add_performance_indexes',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter_subscribers`
--

DROP TABLE IF EXISTS `newsletter_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newsletter_subscribers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `subscribed_at` timestamp NULL DEFAULT NULL,
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  `unsubscribe_token` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `newsletter_subscribers_email_unique` (`email`),
  UNIQUE KEY `newsletter_subscribers_unsubscribe_token_unique` (`unsubscribe_token`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter_subscribers`
--

LOCK TABLES `newsletter_subscribers` WRITE;
/*!40000 ALTER TABLE `newsletter_subscribers` DISABLE KEYS */;
INSERT INTO `newsletter_subscribers` VALUES (1,'qa-news-1793937704158@example.com','2026-11-06 04:01:44',NULL,'ifCnQLO9kj3B6BdZPKZAhXx3sD1WcG04PKd2SLha','2026-11-06 04:01:44','2026-11-06 04:01:44');
/*!40000 ALTER TABLE `newsletter_subscribers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `seller_id` bigint(20) unsigned DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `shape` varchar(255) DEFAULT NULL,
  `size` varchar(255) DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `line_total` decimal(10,2) NOT NULL,
  `commission_rate` decimal(5,2) DEFAULT NULL,
  `commission_amount` decimal(10,2) DEFAULT NULL,
  `seller_earning` decimal(10,2) DEFAULT NULL,
  `payout_status` enum('pending','paid') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  KEY `order_items_seller_id_index` (`seller_id`),
  KEY `order_items_payout_status_index` (`payout_status`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_items_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,10,NULL,'Golden French','Almond','S',2,4500.00,9000.00,NULL,NULL,NULL,'pending','2026-11-03 15:24:38','2026-11-03 15:24:38'),(2,2,2,NULL,'Midnight Espresso','Coffin','S',2,2800.00,5600.00,NULL,NULL,NULL,'pending','2026-11-03 15:24:38','2026-11-03 15:24:38'),(3,2,8,NULL,'Espresso Split','Stiletto','S',2,2900.00,5800.00,NULL,NULL,NULL,'pending','2026-11-03 15:24:38','2026-11-03 15:24:38'),(4,3,4,NULL,'Ivory Blossom Vine','Almond','S',2,3500.00,7000.00,NULL,NULL,NULL,'pending','2026-11-03 15:24:38','2026-11-03 15:24:38'),(5,4,1,NULL,'Rosy Quartz Ombre','Almond','S',2,2500.00,5000.00,NULL,NULL,NULL,'pending','2026-11-03 15:24:38','2026-11-03 15:24:38'),(6,4,5,NULL,'Modern French Matte','Almond','S',1,2600.00,2600.00,NULL,NULL,NULL,'pending','2026-11-03 15:24:38','2026-11-03 15:24:38'),(7,4,6,NULL,'Gilded Rose Dust','Coffin','S',2,3200.00,6400.00,NULL,NULL,NULL,'pending','2026-11-03 15:24:38','2026-11-03 15:24:38'),(8,5,4,NULL,'Ivory Blossom Vine','Almond','S',2,3500.00,7000.00,NULL,NULL,NULL,'pending','2026-11-03 15:24:38','2026-11-03 15:24:38'),(9,5,7,NULL,'Minimalist Dot','Square','S',2,1800.00,3600.00,NULL,NULL,NULL,'pending','2026-11-03 15:24:38','2026-11-03 15:24:38'),(10,5,8,NULL,'Espresso Split','Stiletto','S',1,2900.00,2900.00,NULL,NULL,NULL,'pending','2026-11-03 15:24:38','2026-11-03 15:24:38'),(11,6,1,NULL,'Rosy Quartz Ombre','Almond','S',1,2500.00,2500.00,NULL,NULL,NULL,'pending','2026-11-03 15:37:21','2026-11-03 15:37:21'),(12,6,7,NULL,'Minimalist Dot','Square','S',2,1800.00,3600.00,NULL,NULL,NULL,'pending','2026-11-03 15:37:21','2026-11-03 15:37:21'),(13,6,8,NULL,'Espresso Split','Stiletto','S',2,2900.00,5800.00,NULL,NULL,NULL,'pending','2026-11-03 15:37:21','2026-11-03 15:37:21'),(14,7,1,NULL,'Rosy Quartz Ombre','Almond','S',1,2500.00,2500.00,NULL,NULL,NULL,'pending','2026-11-03 15:37:21','2026-11-03 15:37:21'),(15,7,3,NULL,'Classic Pearl Mauve','Almond','S',2,2200.00,4400.00,NULL,NULL,NULL,'pending','2026-11-03 15:37:21','2026-11-03 15:37:21'),(16,8,5,NULL,'Modern French Matte','Almond','S',2,2600.00,5200.00,NULL,NULL,NULL,'pending','2026-11-03 15:37:22','2026-11-03 15:37:22'),(17,8,6,NULL,'Gilded Rose Dust','Coffin','S',1,3200.00,3200.00,NULL,NULL,NULL,'pending','2026-11-03 15:37:22','2026-11-03 15:37:22'),(18,8,10,NULL,'Golden French','Almond','S',2,4500.00,9000.00,NULL,NULL,NULL,'pending','2026-11-03 15:37:22','2026-11-03 15:37:22'),(19,9,10,NULL,'Golden French','Almond','S',1,4500.00,4500.00,NULL,NULL,NULL,'pending','2026-11-03 15:37:22','2026-11-03 15:37:22'),(20,9,11,NULL,'Matte Rose','Coffin','S',2,3800.00,7600.00,NULL,NULL,NULL,'pending','2026-11-03 15:37:22','2026-11-03 15:37:22'),(21,10,7,NULL,'Minimalist Dot','Square','S',2,1800.00,3600.00,NULL,NULL,NULL,'pending','2026-11-03 15:37:22','2026-11-03 15:37:22'),(22,11,1,NULL,'Rosy Quartz Ombre','Almond Medium','S',1,2500.00,2500.00,0.00,0.00,0.00,'pending','2026-11-04 16:11:58','2026-11-04 16:11:58'),(23,12,1,NULL,'Rosy Quartz Ombre',NULL,NULL,2,2500.00,5000.00,0.00,0.00,0.00,'pending','2026-11-06 04:00:51','2026-11-06 04:00:51'),(24,13,1,NULL,'Rosy Quartz Ombre','Almond Medium','S',1,2500.00,2500.00,0.00,0.00,0.00,'pending','2026-11-06 14:21:00','2026-11-06 14:21:00'),(25,14,1,NULL,'Rosy Quartz Ombre','Almond Medium','S',1,2500.00,2500.00,0.00,0.00,0.00,'pending','2026-11-06 14:21:25','2026-11-06 14:21:25');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `order_number` varchar(255) NOT NULL,
  `access_token` varchar(64) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `area` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `payment_method` varchar(255) NOT NULL,
  `transaction_reference` varchar(255) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `shipping` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `coupon_code` varchar(255) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `stock_restored_at` timestamp NULL DEFAULT NULL,
  `payment_status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_number_unique` (`order_number`),
  UNIQUE KEY `orders_access_token_unique` (`access_token`),
  KEY `orders_status_index` (`status`),
  KEY `orders_email_index` (`email`),
  KEY `orders_user_id_index` (`user_id`),
  KEY `orders_created_at_index` (`created_at`),
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,NULL,'ALN-KLAOUADR','mW951xDjEUEUynj9RyWllcqZBRLeXM1MOC7WNvdHq2PnJZji','Ayesha','Khan','ayesha.khan@example.com','+92 300 1112233','House 12, Street 4, DHA Phase 5','Karachi',NULL,NULL,'cod',NULL,9000.00,0.00,0.00,NULL,9000.00,'pending',NULL,'pending','2026-11-03 15:24:38','2026-11-03 15:24:38'),(2,NULL,'ALN-WDEHUNXV','t0KqXZMpILp9N1tIiVluG7re4rckH3Z7uiryaJgdykvJG7lJ','Sana','Malik','sana.malik@example.com','+92 301 2223344','Flat 3B, Gulberg III','Lahore',NULL,NULL,'bank_transfer',NULL,11400.00,250.00,0.00,NULL,11650.00,'processing',NULL,'pending','2026-11-03 15:24:38','2026-11-03 15:24:38'),(3,NULL,'ALN-DDNXO8PP','f734JanGQr7F7JTlXYevS4cSELL6EksJcvzQgh3RquGxm5XT','Fatima','Raza','fatima.raza@example.com','+92 302 3334455','House 7, F-10/2','Islamabad',NULL,NULL,'jazzcash_easypaisa',NULL,7000.00,250.00,0.00,NULL,7250.00,'shipped',NULL,'pending','2026-11-03 15:24:38','2026-11-03 15:24:38'),(4,NULL,'ALN-WRYROYGO','aLqeOV5jzpafearm67QxiiBRvwNgjxcuU5KNVcuNT3z7XFVB','Zainab','Sheikh','zainab.sheikh@example.com','+92 303 4445566','Apartment 9, Clifton Block 2','Karachi',NULL,NULL,'cod',NULL,14000.00,0.00,0.00,NULL,14000.00,'delivered',NULL,'pending','2026-11-03 15:24:38','2026-11-03 15:24:38'),(5,NULL,'ALN-VORTGAIN','FBpHu6Omec9tgjyBNG6JKG0JoA9zfIhKlsKhzMXyfl49F3AR','Mahnoor','Ali','mahnoor.ali@example.com','+92 304 5556677','House 21, Satellite Town','Rawalpindi',NULL,NULL,'bank_transfer',NULL,13500.00,250.00,0.00,NULL,13750.00,'confirmed',NULL,'pending','2026-11-03 15:24:38','2026-11-03 15:24:38'),(6,NULL,'ALN-2G6VXG7L','VsB4jFP6JMPB48PIozvyJlacQYxls2vg5VJIrnjGyxtMY04u','Ayesha','Khan','ayesha.khan@example.com','+92 300 1112233','House 12, Street 4, DHA Phase 5','Karachi',NULL,NULL,'cod',NULL,11900.00,0.00,0.00,NULL,11900.00,'pending',NULL,'pending','2026-11-03 15:37:21','2026-11-03 15:37:21'),(7,NULL,'ALN-A6LA6NET','BAp7pr9WA6L33trUA8xuEkKWb3w1An7aRvvw7yVCTwaMXroO','Sana','Malik','sana.malik@example.com','+92 301 2223344','Flat 3B, Gulberg III','Lahore',NULL,NULL,'bank_transfer',NULL,6900.00,250.00,0.00,NULL,7150.00,'processing',NULL,'pending','2026-11-03 15:37:21','2026-11-03 15:37:21'),(8,NULL,'ALN-X82QAOCF','RP16ZLoz7lMCEjhaSabm3EnyDj8MhY4kCyazwIhqnDrAgGhQ','Fatima','Raza','fatima.raza@example.com','+92 302 3334455','House 7, F-10/2','Islamabad',NULL,NULL,'jazzcash_easypaisa',NULL,17400.00,250.00,0.00,NULL,17650.00,'shipped',NULL,'pending','2026-11-03 15:37:22','2026-11-03 15:37:22'),(9,NULL,'ALN-0SAXFQ3O','tihaNmRl2wzqQO0XfVmxx9RnKEsLBz6mgv1mVpqazMft3U0R','Zainab','Sheikh','zainab.sheikh@example.com','+92 303 4445566','Apartment 9, Clifton Block 2','Karachi',NULL,NULL,'cod',NULL,12100.00,0.00,0.00,NULL,12100.00,'delivered',NULL,'pending','2026-11-03 15:37:22','2026-11-03 15:37:22'),(10,NULL,'ALN-SKMLACIM','ZESh818p0gqrWonoJIsiwdrceCwNjahqi2bdkfquzecvCuZB','Mahnoor','Ali','mahnoor.ali@example.com','+92 304 5556677','House 21, Satellite Town','Rawalpindi',NULL,NULL,'bank_transfer',NULL,3600.00,250.00,0.00,NULL,3850.00,'confirmed',NULL,'pending','2026-11-03 15:37:22','2026-11-03 15:37:22'),(11,NULL,'ALN-HKRVVNPX','qRpjEE1F0uVePE8BineWfRvvFbCz5mfjlNDI5NjxtvSNtaus','Mohsin','Gohar','mohsingohar759@gmail.com','+923112277425','House# 8/8, Street# 10, Area 5-C, Landhi# 6, Karachi','Karachi','Korangi / Landhi','74900','online_payment','Online Payment (Pending Contact) (Note: jhbbhljk.nmlknl)',2500.00,0.00,0.00,NULL,2500.00,'pending',NULL,'pending','2026-11-04 16:11:58','2026-11-04 16:11:58'),(12,NULL,'ALN-BCWAOUIJ','RvFksJ5HLAea1dK8bbdR9hORoxo4MDca4ILvjNXmfVYILSJe','QA','Tester','qa1793937650796@example.com','+92 300 1234567','House 123, Street 4, DHA Phase 6','Karachi','DHA (Defence)','75500','sadapay','Online Payment (Pending Contact) (Note: E2E automated test order)',5000.00,0.00,500.00,'WELCOME10',4500.00,'pending',NULL,'pending','2026-11-06 04:00:51','2026-11-06 04:00:51'),(13,1,'ALN-OLNKFMGV','Y2YNi0ay8ScWVM8m2Pa5f5ujTyNsFQzG0ODpDPBxqP13sSpM','Demo','QA','demo@alishenails.com','+923001234567+92 300 1234567','Test Address 123','Karachi','Clifton',NULL,'sadapay','Online Payment (Pending Contact)',2500.00,0.00,0.00,NULL,2500.00,'pending',NULL,'pending','2026-11-06 14:21:00','2026-11-06 14:21:00'),(14,1,'ALN-ITIPJR3O','G2ebjoVJ6rXjOccCN6D3qxTZiQahfpRD6D6ZxePAjiAO40ym','Demo','QA','demo@alishenails.com','+923001234567+92 300 1234567','Test Address 123','Karachi','Clifton',NULL,'sadapay','Online Payment (Pending Contact)',2500.00,0.00,0.00,NULL,2500.00,'pending',NULL,'pending','2026-11-06 14:21:25','2026-11-06 14:21:25');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `seller_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `sku` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `compare_at_price` decimal(10,2) DEFAULT NULL,
  `short_description` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `shape` varchar(255) DEFAULT NULL,
  `length` varchar(255) DEFAULT NULL,
  `finish` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `gallery` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gallery`)),
  `media` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`media`)),
  `badge` varchar(255) DEFAULT NULL,
  `is_best_seller` tinyint(1) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `stock` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `whats_included` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`whats_included`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_slug_unique` (`slug`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  KEY `1` (`seller_id`),
  KEY `products_is_active_index` (`is_active`),
  KEY `products_category_id_index` (`category_id`),
  KEY `products_is_active_is_best_seller_index` (`is_active`,`is_best_seller`),
  KEY `products_is_active_price_index` (`is_active`,`price`),
  CONSTRAINT `1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,2,NULL,'Rosy Quartz Ombre','rosy-quartz-ombre','ALN-HJI0BC',2500.00,NULL,'Hand-painted ombre with delicate gold leaf accents.','Hand-painted elegance with a seamless gradient and delicate gold leaf accents. Crafted for a salon-quality finish at home. Reusable up to 5 times with proper care.','Almond','Medium','Matte','rosy-quartz-ombre.jpg','[\"rosy-quartz-ombre.jpg\",\"rosy-quartz-ombre-worn.jpg\",\"rosy-quartz-ombre-detail.jpg\",\"rosy-quartz-ombre-kit.jpg\"]',NULL,'NEW',1,1,10,1,'[\"10 Hand-painted press-on nails\",\"1 Mini nail file & buffer\",\"1 Wooden cuticle pusher\",\"24 Adhesive sticky tabs\",\"1 Liquid nail glue (2g)\",\"2 Alcohol prep pads\"]','2026-11-03 15:24:37','2026-11-06 14:21:25'),(2,3,NULL,'Midnight Espresso','midnight-espresso','ALN-RMHVWD',2800.00,NULL,'Deep espresso tone with a soft gold edge line.','A rich, deep espresso shade finished with a delicate soft-gold edge line for a bold, elegant look.','Coffin','Long','Glossy','midnight-espresso.jpg','[\"midnight-espresso.jpg\"]',NULL,NULL,0,0,39,1,'[\"10 Hand-painted press-on nails\",\"1 Mini nail file & buffer\",\"1 Wooden cuticle pusher\",\"24 Adhesive sticky tabs\",\"1 Liquid nail glue (2g)\",\"2 Alcohol prep pads\"]','2026-11-03 15:24:37','2026-11-03 15:24:37'),(3,1,NULL,'Classic Pearl Mauve','classic-pearl-mauve','ALN-RMYIUP',2200.00,NULL,'A timeless mauve shade, perfect for every day.','Our signature everyday mauve — soft, pearlescent, and endlessly wearable.','Almond','Medium','Matte','classic-pearl-mauve.jpg','[\"classic-pearl-mauve.jpg\"]',NULL,'BEST SELLER',1,1,51,1,'[\"10 Hand-painted press-on nails\",\"1 Mini nail file & buffer\",\"1 Wooden cuticle pusher\",\"24 Adhesive sticky tabs\",\"1 Liquid nail glue (2g)\",\"2 Alcohol prep pads\"]','2026-11-03 15:24:37','2026-11-03 15:24:37'),(4,4,NULL,'Ivory Blossom Vine','ivory-blossom-vine','ALN-WDGLQJ',3500.00,NULL,'Botanical hand-painted vine detailing on ivory base.','Delicate hand-painted florals over an ivory base — romantic and detailed.','Almond','Medium','Matte','ivory-blossom-vine.jpg','[\"ivory-blossom-vine.jpg\"]',NULL,NULL,0,0,44,1,'[\"10 Hand-painted press-on nails\",\"1 Mini nail file & buffer\",\"1 Wooden cuticle pusher\",\"24 Adhesive sticky tabs\",\"1 Liquid nail glue (2g)\",\"2 Alcohol prep pads\"]','2026-11-03 15:24:37','2026-11-03 15:24:37'),(5,1,NULL,'Modern French Matte','modern-french-matte','ALN-L4UDCB',2600.00,NULL,'A modern take on the French manicure, in matte mauve.','A modern take on the classic French manicure, reimagined in a matte mauve tone.','Almond','Medium','Matte','modern-french-matte.jpg','[\"modern-french-matte.jpg\"]',NULL,NULL,0,0,30,1,'[\"10 Hand-painted press-on nails\",\"1 Mini nail file & buffer\",\"1 Wooden cuticle pusher\",\"24 Adhesive sticky tabs\",\"1 Liquid nail glue (2g)\",\"2 Alcohol prep pads\"]','2026-11-03 15:24:37','2026-11-03 15:24:37'),(6,4,NULL,'Gilded Rose Dust','gilded-rose-dust','ALN-FJTABV',3200.00,NULL,'Blush gold glitter, medium coffin, set of 10.','A shimmering blush-gold glitter finish for special occasions and everyday sparkle alike.','Coffin','Medium','Glitter/Chrome','gilded-rose-dust.jpg','[\"gilded-rose-dust.jpg\"]',NULL,'LIMITED',0,0,25,1,'[\"10 Hand-painted press-on nails\",\"1 Mini nail file & buffer\",\"1 Wooden cuticle pusher\",\"24 Adhesive sticky tabs\",\"1 Liquid nail glue (2g)\",\"2 Alcohol prep pads\"]','2026-11-03 15:24:37','2026-11-03 15:24:37'),(7,1,NULL,'Minimalist Dot','minimalist-dot','ALN-F7CHL2',1800.00,NULL,'Clean white base with a single accent dot.','A clean, minimal white base finished with a single delicate accent dot.','Square','Short','Glossy','minimalist-dot.jpg','[\"minimalist-dot.jpg\"]',NULL,NULL,0,0,17,1,'[\"10 Hand-painted press-on nails\",\"1 Mini nail file & buffer\",\"1 Wooden cuticle pusher\",\"24 Adhesive sticky tabs\",\"1 Liquid nail glue (2g)\",\"2 Alcohol prep pads\"]','2026-11-03 15:24:37','2026-11-03 15:24:37'),(8,3,NULL,'Espresso Split','espresso-split','ALN-HBPZOP',2900.00,NULL,'Bold espresso and ivory colour-split design.','A striking colour-split design pairing deep espresso with warm ivory.','Stiletto','Long','Glossy','espresso-split.jpg','[\"espresso-split.jpg\"]',NULL,NULL,0,0,53,1,'[\"10 Hand-painted press-on nails\",\"1 Mini nail file & buffer\",\"1 Wooden cuticle pusher\",\"24 Adhesive sticky tabs\",\"1 Liquid nail glue (2g)\",\"2 Alcohol prep pads\"]','2026-11-03 15:24:37','2026-11-03 15:24:37'),(9,2,NULL,'Blush Aura','blush-aura','ALN-L7BMZL',3000.00,NULL,'Soft blush-to-nude gradient with a matte finish.','A soft, sunset-inspired blush gradient finished with a smooth matte topcoat.','Almond','Long','Matte','blush-aura.jpg','[\"blush-aura.jpg\"]',NULL,'NEW',0,0,27,1,'[\"10 Hand-painted press-on nails\",\"1 Mini nail file & buffer\",\"1 Wooden cuticle pusher\",\"24 Adhesive sticky tabs\",\"1 Liquid nail glue (2g)\",\"2 Alcohol prep pads\"]','2026-11-03 15:24:37','2026-11-03 15:24:37'),(10,4,NULL,'Golden French','golden-french','ALN-P5BII7',4500.00,NULL,'French tip reimagined with a soft gold line.','A modern French manicure finished with a fine soft-gold line along the tip.','Almond','Medium','Glossy','golden-french.jpg','[\"golden-french.jpg\"]',NULL,'BEST SELLER',1,0,43,1,'[\"10 Hand-painted press-on nails\",\"1 Mini nail file & buffer\",\"1 Wooden cuticle pusher\",\"24 Adhesive sticky tabs\",\"1 Liquid nail glue (2g)\",\"2 Alcohol prep pads\"]','2026-11-03 15:24:37','2026-11-03 15:24:37'),(11,1,NULL,'Matte Rose','matte-rose','ALN-WVQR0Y',3800.00,NULL,'A muted matte rose, coffin shape, long length.','A muted matte rose shade in a sculpted coffin shape.','Coffin','Long','Matte','matte-rose.jpg','[\"matte-rose.jpg\"]',NULL,NULL,1,0,33,1,'[\"10 Hand-painted press-on nails\",\"1 Mini nail file & buffer\",\"1 Wooden cuticle pusher\",\"24 Adhesive sticky tabs\",\"1 Liquid nail glue (2g)\",\"2 Alcohol prep pads\"]','2026-11-03 15:24:37','2026-11-03 15:24:37'),(12,3,NULL,'Pink Marble','pink-marble','ALN-OX8TNG',3300.00,NULL,'Hand-marbled pink and white swirl design.','A hand-marbled pink and white swirl, no two sets exactly alike.','Square','Medium','Glossy','pink-marble.jpg','[\"pink-marble.jpg\"]',NULL,NULL,1,0,55,1,'[\"10 Hand-painted press-on nails\",\"1 Mini nail file & buffer\",\"1 Wooden cuticle pusher\",\"24 Adhesive sticky tabs\",\"1 Liquid nail glue (2g)\",\"2 Alcohol prep pads\"]','2026-11-03 15:24:37','2026-11-03 15:24:37');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL,
  `comment` text DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `is_verified_purchase` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reviews_product_id_user_id_unique` (`product_id`,`user_id`),
  KEY `reviews_user_id_foreign` (`user_id`),
  KEY `reviews_product_id_is_approved_index` (`product_id`,`is_approved`),
  CONSTRAINT `reviews_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,1,NULL,'Ayesha K.',5,'Fit perfectly and lasted almost two weeks. Will reorder!',1,0,'2026-11-03 15:24:37','2026-11-03 15:24:37'),(2,1,NULL,'Fatima R.',4,'Beautiful finish, application was easy with the included kit.',1,0,'2026-11-03 15:24:37','2026-11-03 15:24:37'),(3,1,NULL,'Sana M.',5,'Exactly like the photos. So many compliments!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(4,2,NULL,'Ayesha K.',5,'Fit perfectly and lasted almost two weeks. Will reorder!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(5,2,NULL,'Fatima R.',4,'Beautiful finish, application was easy with the included kit.',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(6,2,NULL,'Sana M.',5,'Exactly like the photos. So many compliments!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(7,3,NULL,'Ayesha K.',5,'Fit perfectly and lasted almost two weeks. Will reorder!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(8,3,NULL,'Fatima R.',4,'Beautiful finish, application was easy with the included kit.',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(9,3,NULL,'Sana M.',5,'Exactly like the photos. So many compliments!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(10,4,NULL,'Ayesha K.',5,'Fit perfectly and lasted almost two weeks. Will reorder!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(11,4,NULL,'Fatima R.',4,'Beautiful finish, application was easy with the included kit.',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(12,4,NULL,'Sana M.',5,'Exactly like the photos. So many compliments!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(13,5,NULL,'Ayesha K.',5,'Fit perfectly and lasted almost two weeks. Will reorder!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(14,5,NULL,'Fatima R.',4,'Beautiful finish, application was easy with the included kit.',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(15,5,NULL,'Sana M.',5,'Exactly like the photos. So many compliments!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(16,6,NULL,'Ayesha K.',5,'Fit perfectly and lasted almost two weeks. Will reorder!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(17,6,NULL,'Fatima R.',4,'Beautiful finish, application was easy with the included kit.',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(18,6,NULL,'Sana M.',5,'Exactly like the photos. So many compliments!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(19,7,NULL,'Ayesha K.',5,'Fit perfectly and lasted almost two weeks. Will reorder!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(20,7,NULL,'Fatima R.',4,'Beautiful finish, application was easy with the included kit.',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(21,7,NULL,'Sana M.',5,'Exactly like the photos. So many compliments!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(22,8,NULL,'Ayesha K.',5,'Fit perfectly and lasted almost two weeks. Will reorder!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(23,8,NULL,'Fatima R.',4,'Beautiful finish, application was easy with the included kit.',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(24,8,NULL,'Sana M.',5,'Exactly like the photos. So many compliments!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(25,9,NULL,'Ayesha K.',5,'Fit perfectly and lasted almost two weeks. Will reorder!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(26,9,NULL,'Fatima R.',4,'Beautiful finish, application was easy with the included kit.',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(27,9,NULL,'Sana M.',5,'Exactly like the photos. So many compliments!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(28,10,NULL,'Ayesha K.',5,'Fit perfectly and lasted almost two weeks. Will reorder!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(29,10,NULL,'Fatima R.',4,'Beautiful finish, application was easy with the included kit.',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(30,10,NULL,'Sana M.',5,'Exactly like the photos. So many compliments!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(31,11,NULL,'Ayesha K.',5,'Fit perfectly and lasted almost two weeks. Will reorder!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(32,11,NULL,'Fatima R.',4,'Beautiful finish, application was easy with the included kit.',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(33,11,NULL,'Sana M.',5,'Exactly like the photos. So many compliments!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(34,12,NULL,'Ayesha K.',5,'Fit perfectly and lasted almost two weeks. Will reorder!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(35,12,NULL,'Fatima R.',4,'Beautiful finish, application was easy with the included kit.',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38'),(36,12,NULL,'Sana M.',5,'Exactly like the photos. So many compliments!',1,0,'2026-11-03 15:24:38','2026-11-03 15:24:38');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_subscriptions`
--

DROP TABLE IF EXISTS `seller_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seller_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` bigint(20) unsigned NOT NULL,
  `subscription_plan_id` bigint(20) unsigned NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration_days` int(10) unsigned NOT NULL,
  `status` enum('pending','active','rejected','expired') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cod','bank_transfer','jazzcash_easypaisa') NOT NULL,
  `transaction_reference` varchar(255) DEFAULT NULL,
  `payment_notes` text DEFAULT NULL,
  `starts_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seller_subscriptions_seller_id_foreign` (`seller_id`),
  KEY `seller_subscriptions_subscription_plan_id_foreign` (`subscription_plan_id`),
  KEY `seller_subscriptions_reviewed_by_foreign` (`reviewed_by`),
  KEY `seller_subscriptions_status_index` (`status`),
  KEY `seller_subscriptions_expires_at_index` (`expires_at`),
  CONSTRAINT `seller_subscriptions_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `seller_subscriptions_seller_id_foreign` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `seller_subscriptions_subscription_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_subscriptions`
--

LOCK TABLES `seller_subscriptions` WRITE;
/*!40000 ALTER TABLE `seller_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sellers`
--

DROP TABLE IF EXISTS `sellers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sellers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `instagram_handle` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `product_details` text NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sellers_email_unique` (`email`),
  KEY `sellers_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sellers`
--

LOCK TABLES `sellers` WRITE;
/*!40000 ALTER TABLE `sellers` DISABLE KEYS */;
/*!40000 ALTER TABLE `sellers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('4GfU9kFrF3qtuq8rmNMaQVFNwY7mYxtmes0XWggS',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMVhYUmxmQ2M1NHNwZVUxTlZiMGY0MTdRUjFFVWdPQ3c2VU9nUWJLSSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAxNy9zaG9wIjtzOjU6InJvdXRlIjtzOjEwOiJzaG9wLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1793978543),('BfwAyZkORj1aN9flk1xn4ARLK4di23SMxQXd34W0',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.0.0 Safari/537.36','YTo3OntzOjY6Il90b2tlbiI7czo0MDoiM1ZFaHBWdlRLUkFES1d4VHhVbGQxcDFHbnNRM3NSSlpNQXNJbmM5ViI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAxNy9hY2NvdW50L29yZGVycyI7czo1OiJyb3V0ZSI7czoxNDoiYWNjb3VudC5vcmRlcnMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUyOiJsb2dpbl9hZG1pbl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7czoxOToicGFzc3dvcmRfaGFzaF9hZG1pbiI7czo2NDoiMTQxNWQ5MDFjMTNmMjA2ZmJjNWFkNDYzOGNjZmFkMzY3MTY2ZWE0ZTU5NDM4ZTNiNzMzZGQ2NDE0NTFhNjUyMyI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiMzllMjU3ZmE5NzBmYmExODc1N2JjNjYxNDhhNjU0NTVjZTFmMTU5MTMyMTZjZTc4OTIzNTc1ODUxMWRkZDIyNyI7fQ==',1793975221),('Bj17vqt9PXFLk8kPQcHm1TxzBML4NGDLZ7JxPCj8',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.22621.4249','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRDBjTVpSUUdNVzhmRVNyREs1d1BRUFV2ZEdpWEIzRjA4ZE5wV3lEUCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAxNyI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1793974623),('F4uxEYUGycTM7ZlQJHiLER8blMsSvXpIdvQVKt1N',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) OpenCode/2.2.0 Chrome/152.0.7977.167 Electron/44.9.1 Safari/537.36','YTo2OntzOjY6Il90b2tlbiI7czo0MDoiY3dZZWc1UUdxU21td0xTdTBjcHRBbkNtTDg4ZlRkdnpwMVRNVmlrcyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAxNyI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiMzllMjU3ZmE5NzBmYmExODc1N2JjNjYxNDhhNjU0NTVjZTFmMTU5MTMyMTZjZTc4OTIzNTc1ODUxMWRkZDIyNyI7czoxMToiYWxpc2hlX2NhcnQiO2E6MTp7czoxMjoidWZDNG5Ed2lOSDVWIjthOjg6e3M6MTA6InByb2R1Y3RfaWQiO2k6MjtzOjQ6Im5hbWUiO3M6MTc6Ik1pZG5pZ2h0IEVzcHJlc3NvIjtzOjQ6InNsdWciO3M6MTc6Im1pZG5pZ2h0LWVzcHJlc3NvIjtzOjU6ImltYWdlIjtzOjIxOiJtaWRuaWdodC1lc3ByZXNzby5qcGciO3M6NToicHJpY2UiO2Q6MjgwMDtzOjU6InNoYXBlIjtzOjExOiJDb2ZmaW4gTG9uZyI7czo0OiJzaXplIjtzOjE6IlMiO3M6MzoicXR5IjtpOjE7fX19',1793978689),('GaX9X3wa28noXOrkgvQmPFXHmNPDiSWnhHgQ2A1C',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.0.0 Safari/537.36','YTo3OntzOjY6Il90b2tlbiI7czo0MDoiNkRqRmY3U0VtSFYyYmhvM2xpNFFraTlYMEdzdjZjZm10NzMyYjVFUyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAxNy9hY2NvdW50L29yZGVycyI7czo1OiJyb3V0ZSI7czoxNDoiYWNjb3VudC5vcmRlcnMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUyOiJsb2dpbl9hZG1pbl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7czoxOToicGFzc3dvcmRfaGFzaF9hZG1pbiI7czo2NDoiMTQxNWQ5MDFjMTNmMjA2ZmJjNWFkNDYzOGNjZmFkMzY3MTY2ZWE0ZTU5NDM4ZTNiNzMzZGQ2NDE0NTFhNjUyMyI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiMzllMjU3ZmE5NzBmYmExODc1N2JjNjYxNDhhNjU0NTVjZTFmMTU5MTMyMTZjZTc4OTIzNTc1ODUxMWRkZDIyNyI7fQ==',1793975122),('LeoGjfWAjMaEGTP93510ZZVoqIkw8SvtHaUZ5d6O',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMkJRYTNqRkFPQWF0TzJDb3BmUU42WmZaMGlPaExDMVBBNGY0U1ozbiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAxNy9mb3Jnb3QtcGFzc3dvcmQiO3M6NToicm91dGUiO3M6MTY6InBhc3N3b3JkLnJlcXVlc3QiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793975026),('mMEVEKgcYBKDSylED7GdkFjT1TBVJKP4TOcGPP3K',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoicDRaWXdNNEVJeE5HRldYaTI4eUQxOVo1UjhnYkNZemIyR3dqVlViaSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAxNy9hY2NvdW50L29yZGVycyI7czo1OiJyb3V0ZSI7czoxNDoiYWNjb3VudC5vcmRlcnMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO3M6MTc6InBhc3N3b3JkX2hhc2hfd2ViIjtzOjY0OiIzOWUyNTdmYTk3MGZiYTE4NzU3YmM2NjE0OGE2NTQ1NWNlMWYxNTkxMzIxNmNlNzg5MjM1NzU4NTExZGRkMjI3Ijt9',1793974889),('QNGZUxU8ENx4KoO9HHDTVKAfgAIJnESVgSdHFv1l',NULL,'127.0.0.1','','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYTVhcFdmTVFoRW1mMFRmeVBzeTBqM0VsYlc3SmNIYVA2dEJ4ZXlnNiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly8xMjcuMC4wLjE6ODAxNy9zaXRlbWFwLnhtbCI7czo1OiJyb3V0ZSI7czo3OiJzaXRlbWFwIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1793975425),('u8WTtDY2rcnk0vxju7hWgk5I8CKYLtMnOiEzLILi',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.0.0 Safari/537.36','YTo3OntzOjY6Il90b2tlbiI7czo0MDoiWEVhTzJiTWhaTFo1R3gyU1hBWTNmb1NMWjA3Z3dIM1JVS0ljTHZFSCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAxNy9hY2NvdW50L29yZGVycyI7czo1OiJyb3V0ZSI7czoxNDoiYWNjb3VudC5vcmRlcnMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUyOiJsb2dpbl9hZG1pbl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7czoxOToicGFzc3dvcmRfaGFzaF9hZG1pbiI7czo2NDoiMTQxNWQ5MDFjMTNmMjA2ZmJjNWFkNDYzOGNjZmFkMzY3MTY2ZWE0ZTU5NDM4ZTNiNzMzZGQ2NDE0NTFhNjUyMyI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiMzllMjU3ZmE5NzBmYmExODc1N2JjNjYxNDhhNjU0NTVjZTFmMTU5MTMyMTZjZTc4OTIzNTc1ODUxMWRkZDIyNyI7fQ==',1793975321),('ulW70MntBsNdiDgjUkLveEq4SOHrz2pNNKul4Ls1',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiWHdyWVcxY2xNZzRZRUFRd2hhQW1zd1Y0MGdTaVhIY1pZWVpQeGpTRyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly8xMjcuMC4wLjE6ODAxNy90cmFjay1vcmRlciI7czo1OiJyb3V0ZSI7czoxNzoidHJhY2stb3JkZXIuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO3M6MTc6InBhc3N3b3JkX2hhc2hfd2ViIjtzOjY0OiIzOWUyNTdmYTk3MGZiYTE4NzU3YmM2NjE0OGE2NTQ1NWNlMWYxNTkxMzIxNmNlNzg5MjM1NzU4NTExZGRkMjI3Ijt9',1793974866),('WdWCaExtFupZBl6YQlSsNpW2IG9yCVx6sJXntZwu',NULL,'127.0.0.1','','YTozOntzOjY6Il90b2tlbiI7czo0MDoiR3dxcXpDaFJyMHd0bkV1NERmSUd4NHgyZFVXVXVyV3lxMFlmT1pjNCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAxNyI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1793974643),('xWEqcri311H8SiqR7FmwYzQaIbEOkgO7tZVkkPiH',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZ0VPNUgxdmN5RFJqcXFZT0RpZlowajRHMTZJWlFENVFiRGNyZVBleiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAxNy9zaG9wIjtzOjU6InJvdXRlIjtzOjEwOiJzaG9wLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1793975375);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_rates`
--

DROP TABLE IF EXISTS `shipping_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shipping_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `city` varchar(255) NOT NULL,
  `area` varchar(255) DEFAULT NULL,
  `delivery_fee` decimal(10,2) NOT NULL,
  `free_shipping_threshold` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shipping_rates_city_area_index` (`city`,`area`),
  KEY `shipping_rates_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_rates`
--

LOCK TABLES `shipping_rates` WRITE;
/*!40000 ALTER TABLE `shipping_rates` DISABLE KEYS */;
INSERT INTO `shipping_rates` VALUES (1,'Karachi','Korangi / Landhi',0.00,NULL,1,'2026-11-03 15:24:37','2026-11-03 15:24:37'),(2,'Karachi','Shah Faisal Colony',0.00,NULL,1,'2026-11-03 15:24:37','2026-11-03 15:24:37'),(3,'Karachi','Malir / Model Colony',0.00,NULL,1,'2026-11-03 15:24:37','2026-11-03 15:24:37'),(4,'Karachi','Gulshan-e-Iqbal',0.00,NULL,1,'2026-11-03 15:24:37','2026-11-03 15:24:37'),(5,'Karachi','Gulistan-e-Johar',0.00,NULL,1,'2026-11-03 15:24:37','2026-11-03 15:24:37'),(6,'Karachi','PECHS / Tariq Road / Bahadurabad',0.00,NULL,1,'2026-11-03 15:24:37','2026-11-03 15:24:37'),(7,'Karachi','Saddar / Cantt / Garden',0.00,NULL,1,'2026-11-03 15:24:37','2026-11-03 15:24:37'),(8,'Karachi','Federal B Area (F.B Area)',0.00,NULL,1,'2026-11-03 15:24:37','2026-11-03 15:24:37'),(9,'Karachi','DHA (Defence)',0.00,NULL,1,'2026-11-03 15:24:37','2026-11-03 15:24:37'),(10,'Karachi','Clifton',0.00,NULL,1,'2026-11-03 15:24:37','2026-11-03 15:24:37'),(11,'Karachi','Nazimabad / North Nazimabad',0.00,NULL,1,'2026-11-03 15:24:37','2026-11-03 15:24:37'),(12,'Karachi','North Karachi / New Karachi',0.00,NULL,1,'2026-11-03 15:24:37','2026-11-03 15:24:37'),(13,'Karachi','Scheme 33 / Gulshan-e-Maymar',0.00,NULL,1,'2026-11-03 15:24:37','2026-11-03 15:24:37'),(14,'Karachi','Orangi / Site / Baldia',0.00,NULL,1,'2026-11-03 15:24:37','2026-11-03 15:24:37'),(15,'Karachi','Bahria Town Karachi',0.00,NULL,1,'2026-11-03 15:24:37','2026-11-03 15:24:37'),(16,'Karachi','Other Karachi Areas',0.00,NULL,1,'2026-11-03 15:24:37','2026-11-03 15:24:37'),(17,'Karachi',NULL,0.00,NULL,1,'2026-11-03 15:24:37','2026-11-03 15:24:37');
/*!40000 ALTER TABLE `shipping_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_plans`
--

DROP TABLE IF EXISTS `subscription_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `duration_days` int(10) unsigned NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscription_plans_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_plans`
--

LOCK TABLES `subscription_plans` WRITE;
/*!40000 ALTER TABLE `subscription_plans` DISABLE KEYS */;
INSERT INTO `subscription_plans` VALUES (1,'Monthly Creator',30,2500.00,'List unlimited custom nail designs in the Alishe Nails marketplace for 30 days with 0% platform commission.',1,'2026-11-03 15:24:39','2026-11-03 15:24:39'),(2,'Quarterly Pro',90,6500.00,'List unlimited custom nail designs for 3 months with priority storefront placement and 0% commission.',1,'2026-11-03 15:24:39','2026-11-03 15:24:39'),(3,'Annual Partner',365,22000.00,'Full 1-year verified storefront membership with featured collection tags and dedicated brand support.',1,'2026-11-03 15:24:39','2026-11-03 15:24:39');
/*!40000 ALTER TABLE `subscription_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Demo Customer','demo@alishenails.com','+92 300 1234567',NULL,'$2y$12$IrtG4wsW5ZRCAwdNfKmjp.lV/OvrLoNKOjmuUgUkkfy0HsNNfCH8W','oo3jxdcLUCPgoIgKZBDr0HsmDVZ5VAmLjlT3CCm5qR40vVoh1OtGJRDPh57R','2026-11-03 15:24:37','2026-11-03 15:24:37');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlists`
--

DROP TABLE IF EXISTS `wishlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wishlists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wishlists_user_id_product_id_unique` (`user_id`,`product_id`),
  KEY `wishlists_product_id_foreign` (`product_id`),
  CONSTRAINT `wishlists_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlists_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlists`
--

LOCK TABLES `wishlists` WRITE;
/*!40000 ALTER TABLE `wishlists` DISABLE KEYS */;
INSERT INTO `wishlists` VALUES (1,1,1,'2026-11-06 04:01:06','2026-11-06 04:01:06'),(2,1,2,'2026-11-06 15:24:18','2026-11-06 15:24:18');
/*!40000 ALTER TABLE `wishlists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'alishe_nails'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-06 20:30:14
