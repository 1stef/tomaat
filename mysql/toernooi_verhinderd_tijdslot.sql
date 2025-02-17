CREATE TABLE `verhinderd_tijdslot` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `toernooi_id` int(11) DEFAULT NULL,
  `inschrijving_id` int(11) DEFAULT NULL,
  `dagnummer` int(11) DEFAULT NULL,
  `slotnummer` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=182 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci