<?php
declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Inschrijving;
use App\Service\MySQLDB;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @method Inschrijving|null find($id, $lockMode = null, $lockVersion = null)
 * @method Inschrijving|null findOneBy(array $criteria, array $orderBy = null)
 * @method Inschrijving[]    findAll()
 * @method Inschrijving[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InschrijvingRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly MySQLDB $mySQLDB,
        private readonly LoggerInterface $logger)
    {
        parent::__construct($registry, Inschrijving::class);
    }

    public function alleInschrijvingen(int $toernooiId): false|array
    {
        $query =
            "SELECT id, categorie, cat_type, deelnemerA, deelnemerB, ranking_enkel, ranking_dubbel
             FROM inschrijving
                INNER JOIN deelnemer ON inschrijving.deelnemerA = deelnemer.bondsnummer
             WHERE inschrijving.toernooi_id = :toernooi_id ";
        return $this->mySQLDB->PDO_query_all($query, ['toernooi_id' => $toernooiId]);
    }

    /**
     * Haal email en naam van alle inschrijvers voor een toernooi op
     * Wordt gebruikt om iedereen een mail te sturen als bv. de wedstrijden gepland zijn
     */
    public function getAlleInschrijvers(int $toernooiId): false|array
    {
        $query =
            "SELECT user.email, deelnemer.naam
            FROM inschrijving
                 INNER JOIN deelnemer
                    ON deelnemerA = deelnemer.bondsnummer
                    OR deelnemerB = deelnemer.bondsnummer
                 INNER JOIN user
                    ON deelnemer.user_id = user.id
            WHERE inschrijving.toernooi_id = :toernooi_id
            GROUP BY user.email";

        return $this->mySQLDB->PDO_query_all($query, ['toernooi_id' => $toernooiId]);
    }

    public function getCategorieInschrijvingen(int $toernooiId, string $categorie): false|array
    {
        $query =
            "SELECT id, ranking_effectief, categorie, aantal
             FROM inschrijving
		     WHERE toernooi_id = :toernooi_id
		        AND categorie = :categorie
		        AND actief = 1
            ORDER BY ranking_effectief";
        return $this->mySQLDB->PDO_query_all($query, ['toernooi_id' => $toernooiId, 'categorie' => $categorie]);
    }

    public function getBondsnummers(int $toernooiId): false|array
    {
        $query =  "SELECT id, deelnemerA, deelnemerB FROM inschrijving WHERE toernooi_id = :toernooi_id";
        return $this->mySQLDB->PDO_query_all($query, ['toernooi_id' => $toernooiId]);
    }

    public function toonInschrijvingen(int $toernooi_id, string $categorie): false|array
    {
        $query =
            "SELECT
                inschrijving.id,
                inschrijving.categorie, 
                deelnemerA,
                naam AS naamA,
                deelnemerB,
                (SELECT naam FROM deelnemer WHERE bondsnummer = inschrijving.deelnemerB) AS naamB,
                aantal AS aantal_gevraagd, 
                COUNT(tijdslot) AS aantal_gepland
            FROM inschrijving
                LEFT OUTER JOIN combinations
                    ON inschrijving.id = combinations.deelnemer1
                    OR inschrijving.id = combinations.deelnemer2
                INNER JOIN deelnemer
                    ON inschrijving.deelnemerA = bondsnummer
            WHERE inschrijving.toernooi_id = :toernooi_id
                AND inschrijving.categorie = :categorie
                AND inschrijving.actief = 1 
            GROUP BY inschrijving.id
            ORDER BY inschrijving.id";
        return $this->mySQLDB->PDO_query_all($query, ['toernooi_id' => $toernooi_id, 'categorie' => $categorie]);
    }

    function getExtraWedstrijden(int $toernooiId): false|array
    {
        $query = "
            WITH candidates AS         
              (WITH meer_gevraagd AS
                (SELECT inschrijving.id, inschrijving.categorie, inschrijving.cat_type,
                        deelnemerA, A.naam AS naam, deelnemerB, B.naam AS partner, ranking_effectief, 
                                    aantal AS aantal_gevraagd, COUNT(tijdslot) AS aantal_gepland
                    FROM inschrijving
                    LEFT OUTER JOIN combinations ON 
                        inschrijving.id = combinations.deelnemer1 OR
                        inschrijving.id = combinations.deelnemer2
                    INNER JOIN deelnemer A ON inschrijving.deelnemerA = A.bondsnummer
                    LEFT JOIN deelnemer B ON deelnemerB = B.bondsnummer
                    WHERE inschrijving.toernooi_id = :toernooi_id
                        AND inschrijving.actief = 1
                        AND tijdslot > 0
                    GROUP BY inschrijving.id 
                    HAVING aantal_gepland < aantal_gevraagd
                    ORDER BY categorie, ranking_effectief
                )
                SELECT meer1.categorie, meer1.cat_type, meer1.id AS inschrijving1, meer2.id AS inschrijving2,
                    meer1.ranking_effectief AS rank1, meer2.ranking_effectief AS rank2,
                    (meer1.aantal_gevraagd - meer1.aantal_gepland +
                    meer2.aantal_gevraagd - meer2.aantal_gepland) AS totaaltekort,
                    meer1.aantal_gepland AS gepland1, meer2.aantal_gepland AS gepland2,
                    LEAST(meer1.aantal_gepland, meer2.aantal_gepland) AS min_gepland,
                    (meer1.aantal_gepland + meer2.aantal_gepland) AS totaalgepland
                    FROM meer_gevraagd AS meer1, meer_gevraagd AS meer2
                    WHERE meer1.categorie = meer2.categorie
                      AND meer1.id < meer2.id
                      AND ABS(meer1.ranking_effectief-meer2.ranking_effectief)<1.5
            )
            SELECT candidates.categorie, candidates.cat_type, candidates.inschrijving1, candidates.inschrijving2,
                   TRUNCATE(ABS(rank1-rank2), 2) AS rankdiff, totaaltekort,
                   gepland1, gepland2, min_gepland, totaalgepland 
                FROM candidates LEFT JOIN combinations ON
                    (combinations.deelnemer1 = candidates.inschrijving1
                     AND combinations.deelnemer2 = candidates.inschrijving2) OR
                    (combinations.deelnemer1 = candidates.inschrijving2
                     AND combinations.deelnemer2 = candidates.inschrijving1)
                WHERE tijdslot IS null
                ORDER BY min_gepland, totaalgepland, totaaltekort DESC, rankdiff";

        return $this->mySQLDB->PDO_query_all($query, ['toernooi_id' => $toernooiId]);
    }

    public function getInschrijvingCategorieTotalen(int $toernooiId): false|array
    {
        $query =
            "SELECT 
                cat,
                COUNT(categorie) AS aantal_inschrijvingen,
                SUM(aantal) AS gevraagd_aantal
            FROM
                inschrijving
                    RIGHT OUTER JOIN
                categorie ON inschrijving.categorie = categorie.cat
            WHERE
                inschrijving.toernooi_id = :toernooi_id1
                    AND categorie.toernooi_id = :toernooi_id2
                    AND actief = 1
            GROUP BY cat
            ORDER BY cat";

        return $this->mySQLDB->PDO_query_all($query, ['toernooi_id1' => $toernooiId, 'toernooi_id2' => $toernooiId]);
    }

    /**
     * Om na de wedstrijdplanning nog wedstrijden toe te kunnen voegen, worden eerst alle inschrijvingen opgevraagd waarvoor
     * minder wedstrijden zijn ingepland dan gevraagd.
     */
    public function getIncompleteInschrijvingen(int $toernooiId): false|array
    {
        $query =
            "SELECT
               inschrijving.id,
               inschrijving.categorie,
               deelnemerA,
               A.naam AS naam,
               deelnemerB,
               B.naam AS partner,
               ranking_effectief,
               aantal AS aantal_gevraagd,
               COUNT(tijdslot) AS aantal_gepland
            FROM inschrijving
                 LEFT OUTER JOIN combinations
                    ON inschrijving.id = combinations.deelnemer1
                    OR inschrijving.id = combinations.deelnemer2
                 INNER JOIN deelnemer A ON inschrijving.deelnemerA = A.bondsnummer
                 LEFT JOIN deelnemer B ON deelnemerB = B.bondsnummer
            WHERE inschrijving.toernooi_id = :toernooi_id
              AND inschrijving.actief = 1
              AND tijdslot > 0
            GROUP BY inschrijving.id
            HAVING aantal_gepland < aantal_gevraagd
            ORDER BY categorie, ranking_effectief";

        return $this->mySQLDB->PDO_query_all($query, ['toernooi_id' => $toernooiId]);
    }}
