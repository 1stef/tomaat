<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Wedstrijd;
use App\Service\MySQLDB;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @method Wedstrijd|null find($id, $lockMode = null, $lockVersion = null)
 * @method Wedstrijd|null findOneBy(array $criteria, array $orderBy = null)
 * @method Wedstrijd[]    findAll()
 * @method Wedstrijd[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WedstrijdRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly LoggerInterface $logger,
        private readonly MySQLDB $mysqlDB
    )
    {
        parent::__construct($registry, Wedstrijd::class);
    }

    public function getRanglijstGegevens(int $toernooi_id): false|array
    {
        $query =
            "SELECT deeln, MAX(naam) AS naam, MAX(CASE WHEN partner IS NULL THEN '-' ELSE partner END) AS partner,
                 MAX(categorie) AS cat, MAX(ranking_effectief) AS ranking, 
                 (SUM(gew)-SUM(verl)) AS netto_partijen,
                 (SUM(gsets) - SUM(vsets)) AS netto_sets,
                 (SUM(ggames)-SUM(vgames)) AS netto_games,
                 count(deeln) as gespeeld, SUM(gew), SUM(verl), SUM(gsets), SUM(vsets), SUM(ggames), SUM(vgames)
            FROM (
            SELECT deelnemer1 AS deeln, A.naam AS naam, B.naam AS partner, combinations.categorie, ranking_effectief, IF(winnaar=1, 1, 0) as gew, IF(winnaar=2, 1, 0) AS verl,
                    (IFNULL(set1_team1,0)+IFNULL(set2_team1,0)+IFNULL(set3_team1,0)) AS ggames,
                    (IFNULL(set1_team2,0)+IFNULL(set2_team2,0)+IFNULL(set3_team2,0)) AS vgames,
                    (IF(set1_team1>set1_team2, 1, 0) + IF(set2_team1>set2_team2, 1, 0) + IF(set3_team1>set3_team2, 1, 0)) as gsets,
                    (IF(set1_team1<set1_team2, 1, 0) + IF(set2_team1<set2_team2, 1, 0) + IF(set3_team1<set3_team2, 1, 0)) as vsets
                    FROM wedstrijd 
                    INNER JOIN combinations ON wedstrijd_id = combinations.id
                    INNER JOIN inschrijving ON deelnemer1 = inschrijving.id
                    INNER JOIN deelnemer A ON deelnemerA = A.bondsnummer
                    LEFT JOIN deelnemer B ON deelnemerB = B.bondsnummer
                    WHERE wedstrijd.toernooi_id = :toernooi_id_1 AND wedstrijd_status = 'gespeeld'
            UNION ALL
            SELECT deelnemer2 AS deeln, A.naam AS naam, B.naam AS partner, combinations.categorie, ranking_effectief, IF(winnaar=2, 1, 0) as gew, IF(winnaar=1, 1, 0) AS verl,
                    (IFNULL(set1_team2,0)+IFNULL(set2_team2,0)+IFNULL(set3_team2,0)) AS ggames,
                    (IFNULL(set1_team1,0)+IFNULL(set2_team1,0)+IFNULL(set3_team1,0)) AS vgames,
                    (IF(set1_team1<set1_team2, 1, 0) + IF(set2_team1<set2_team2, 1, 0) + IF(set3_team1<set3_team2, 1, 0)) as gsets,
                    (IF(set1_team1>set1_team2, 1, 0) + IF(set2_team1>set2_team2, 1, 0) + IF(set3_team1>set3_team2, 1, 0)) as vsets
                    FROM wedstrijd 
                    INNER JOIN combinations ON wedstrijd_id = combinations.id
                    INNER JOIN inschrijving ON deelnemer2 = inschrijving.id
                    INNER JOIN deelnemer A ON deelnemerA = A.bondsnummer
                    LEFT JOIN deelnemer B ON deelnemerB = B.bondsnummer
                    WHERE wedstrijd.toernooi_id = :toernooi_id_2 AND wedstrijd_status = 'gespeeld'
            ) AS dummy
            GROUP BY deeln ORDER BY cat, netto_partijen DESC, netto_sets DESC, netto_games DESC";
        $this->logger->info("getRanglijstGegevens: " . $query);
        return $this->mysqlDB->PDO_query_all($query, ['toernooi_id_1' => $toernooi_id, 'toernooi_id_2' => $toernooi_id]);
    }

    public function getUserRanglijstGegevens(int $toernooi_id, int $user_id): false|array
    {
        $categorieen = $this->getUserCategorieen($toernooi_id, $user_id);
        $this->logger->info("getUserRanglijstGegevens, categorieen: " . $categorieen);
        $query =
            "SELECT deeln, MAX(naam) AS naam, MAX(CASE WHEN partner IS NULL THEN '-' ELSE partner END) AS partner,
                 MAX(categorie) AS cat, MAX(ranking_effectief) AS ranking, 
                 (SUM(gew)-SUM(verl)) AS netto_partijen,
                 (SUM(gsets) - SUM(vsets)) AS netto_sets,
                 (SUM(ggames)-SUM(vgames)) AS netto_games,
                 count(deeln) as gespeeld, SUM(gew), SUM(verl), SUM(gsets), SUM(vsets), SUM(ggames), SUM(vgames)
            FROM (
            SELECT deelnemer1 AS deeln, A.naam AS naam, B.naam AS partner, combinations.categorie, ranking_effectief, IF(winnaar=1, 1, 0) as gew, IF(winnaar=2, 1, 0) AS verl,
                    (IFNULL(set1_team1,0)+IFNULL(set2_team1,0)+IFNULL(set3_team1,0)) AS ggames,
                    (IFNULL(set1_team2,0)+IFNULL(set2_team2,0)+IFNULL(set3_team2,0)) AS vgames,
                    (IF(set1_team1>set1_team2, 1, 0) + IF(set2_team1>set2_team2, 1, 0) + IF(set3_team1>set3_team2, 1, 0)) as gsets,
                    (IF(set1_team1<set1_team2, 1, 0) + IF(set2_team1<set2_team2, 1, 0) + IF(set3_team1<set3_team2, 1, 0)) as vsets
                    FROM wedstrijd 
                    INNER JOIN combinations ON wedstrijd_id = combinations.id
                    INNER JOIN inschrijving ON deelnemer1 = inschrijving.id
                    INNER JOIN deelnemer A ON deelnemerA = A.bondsnummer
                    LEFT JOIN deelnemer B ON deelnemerB = B.bondsnummer
                    WHERE wedstrijd.toernooi_id = :toernooi_id_1 AND wedstrijd_status = 'gespeeld'
            UNION ALL
            SELECT deelnemer2 AS deeln, A.naam AS naam, B.naam AS partner, combinations.categorie, ranking_effectief, IF(winnaar=2, 1, 0) as gew, IF(winnaar=1, 1, 0) AS verl,
                    (IFNULL(set1_team2,0)+IFNULL(set2_team2,0)+IFNULL(set3_team2,0)) AS ggames,
                    (IFNULL(set1_team1,0)+IFNULL(set2_team1,0)+IFNULL(set3_team1,0)) AS vgames,
                    (IF(set1_team1<set1_team2, 1, 0) + IF(set2_team1<set2_team2, 1, 0) + IF(set3_team1<set3_team2, 1, 0)) as gsets,
                    (IF(set1_team1>set1_team2, 1, 0) + IF(set2_team1>set2_team2, 1, 0) + IF(set3_team1>set3_team2, 1, 0)) as vsets
                    FROM wedstrijd 
                    INNER JOIN combinations ON wedstrijd_id = combinations.id
                    INNER JOIN inschrijving ON deelnemer2 = inschrijving.id
                    INNER JOIN deelnemer A ON deelnemerA = A.bondsnummer
                    LEFT JOIN deelnemer B ON deelnemerB = B.bondsnummer
                    WHERE wedstrijd.toernooi_id = :toernooi_id_2 AND wedstrijd_status = 'gespeeld'
            ) AS dummy
            WHERE categorie IN ($categorieen)
            GROUP BY deeln ORDER BY cat, netto_partijen DESC, netto_sets DESC, netto_games DESC";
        $this->logger->info("getUserRanglijstGegevens: " . $query);
        return $this->mysqlDB->PDO_query_all($query, ['toernooi_id_1' => $toernooi_id, 'toernooi_id_2' => $toernooi_id]);
    }

    /**
     * getUserCategorieën() is een hulpfunctie, het haalt de categorieën op, waarvoor een deelnemer heeft ingeschreven
     * en retourneert ze in een string van bondsnummers, gescheiden door komma's.
     * Dit is bedoeld om deze string te kunnen gebruiken in een SELECT .. WHERE categorie in ($categorieën)
     */
    private function getUserCategorieen(int $toernooi_id, int $user_id): string
    {
        $query =
            "SELECT categorie FROM inschrijving
                INNER JOIN deelnemer
                    ON inschrijving.deelnemerA = deelnemer.bondsnummer 
                    OR inschrijving.deelnemerB = deelnemer.bondsnummer
                WHERE deelnemer.user_id = :user_id
                    AND inschrijving.toernooi_id = :toernooi_id 
                    AND inschrijving.actief = 1";
        $result_array = $this->mysqlDB->PDO_query_all($query, ['user_id' => $user_id, 'toernooi_id' => $toernooi_id]);

        $categorieen = "'" . join("','", array_column($result_array, 'categorie')) . "'";

        return $categorieen;
    }

    public function getBaanBezetting(int $toernooiId): false|array
    {
        $query =
            "SELECT baan, wedstrijd_id FROM wedstrijd 
            WHERE toernooi_id = :toernooi_id
                AND wedstrijd_status = 'spelend'
            ORDER BY baan";
        $this->logger->info("getBaanBezetting, query: " . $query);

        return $this->mysqlDB->PDO_query_all($query, ['toernooi_id' => $toernooiId]);
    }

}
