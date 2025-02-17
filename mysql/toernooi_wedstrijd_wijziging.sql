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
-- Table structure for table `wedstrijd_wijziging`
--

DROP TABLE IF EXISTS `wedstrijd_wijziging`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wedstrijd_wijziging` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `toernooi_id` int(11) NOT NULL,
  `indiener` int(11) NOT NULL,
  `speler1` int(11) DEFAULT NULL,
  `partner1` int(11) DEFAULT NULL,
  `speler2` int(11) DEFAULT NULL,
  `partner2` int(11) DEFAULT NULL,
  `wijziging_status` varchar(45) NOT NULL DEFAULT 'nieuw' COMMENT 'nieuw, wedstrijd_ok, verhinderingen_ok, akkoorden_gevraagd, tijdslot_ok, definitief, verwijderd',
  `actie` varchar(45) NOT NULL,
  `indiener_veranderd` tinyint(4) NOT NULL DEFAULT '0',
  `wedstrijd_id` int(11) NOT NULL,
  `cat_type` varchar(45) NOT NULL COMMENT '"enkel" of "dubbel"',
  `tijdslot_oud` int(11) DEFAULT NULL,
  `tijdslot_nieuw` int(11) DEFAULT NULL,
  `herplan_optie_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=325 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-03-12 22:43:18
