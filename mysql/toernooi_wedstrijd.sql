-- MySQL dump 10.13  Distrib 8.0.18, for Win64 (x86_64)
--
-- Host: localhost    Database: toernooi
-- ------------------------------------------------------
-- Server version	8.0.18

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
-- Table structure for table `wedstrijd`
--

DROP TABLE IF EXISTS `wedstrijd`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wedstrijd` (
  `wedstrijd_id` int(11) NOT NULL,
  `toernooi_id` int(11) NOT NULL,
  `baan` int(11) DEFAULT NULL,
  `starttijd` datetime DEFAULT NULL,
  `eindtijd` datetime DEFAULT NULL,
  `set1_team1` int(11) DEFAULT NULL,
  `set1_team2` int(11) DEFAULT NULL,
  `set2_team1` int(11) DEFAULT NULL,
  `set2_team2` int(11) DEFAULT NULL,
  `set3_team1` int(11) DEFAULT NULL,
  `set3_team2` int(11) DEFAULT NULL,
  `winnaar` int(11) DEFAULT NULL,
  `opgave` int(11) DEFAULT '0',
  `wedstrijd_status` varchar(15) NOT NULL DEFAULT 'gepland' COMMENT 'gepland, wachtend, spelend, onderbroken of gespeeld',
  `aanwezig_1_a` tinyint(4) DEFAULT NULL,
  `aanwezig_1_b` tinyint(4) DEFAULT NULL,
  `aanwezig_2_a` tinyint(4) DEFAULT NULL,
  `aanwezig_2_b` tinyint(4) DEFAULT NULL,
  `wachtstarttijd` datetime DEFAULT NULL,
  PRIMARY KEY (`wedstrijd_id`),
  CONSTRAINT `FK_wedstrijd_id` FOREIGN KEY (`wedstrijd_id`) REFERENCES `combinations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='wedstrijd uitslagen';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2021-02-12 20:28:55
