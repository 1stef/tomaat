<?php
declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Combinations;
use App\Service\MySQLDB;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @method Combinations|null find($id, $lockMode = null, $lockVersion = null)
 * @method Combinations|null findOneBy(array $criteria, array $orderBy = null)
 * @method Combinations[]    findAll()
 * @method Combinations[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CombinationsRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly MySQLDB $mySQLDB,
        private readonly LoggerInterface $logger)
    {
        parent::__construct($registry, Combinations::class);
    }

    public function clearCombinations(int $toernooiId): void
    {
        $query = "UPDATE combinations SET tijdslot = null WHERE toernooi_id = :toernooi_id";
        $this->mySQLDB->PDO_execute($query, ['toernooi_id' => $toernooiId]);
    }

    public function deleteCombinations(int $toernooiId, string $categorie): void
    {
        $query = "DELETE FROM combinations
                  WHERE toernooi_id = :toernooi_id
                    AND categorie = :categorie";
        $this->mySQLDB->PDO_execute($query, ['toernooi_id' => $toernooiId, 'categorie' => $categorie]);
    }

    public function getCombinations(int $toernooiId): false|array
    {
        $query = "SELECT id, deelnemer1, deelnemer2, tijdslot, plan_ronde FROM combinations
                  WHERE toernooi_id = :toernooi_id ORDER BY plan_ronde ASC";
        return $this->mySQLDB->PDO_query_all($query, ['toernooi_id' => $toernooiId]);
    }

    public function toonWedstrijden(int $toernooiId): false|array
    {
        return $this->toonWedstrijdenWhere($toernooiId, "");
    }

    private function toonWedstrijdenWhere(int $toernooiId, string $where_clause): false|array
    {
        //retourneer alle geplande wedstrijden op planvolgorde:
        $query =
            "SELECT combinations.id AS wedstrijd_id, combinations.categorie, categorie.cat_type, deelnemer1, deelnemer2,
                     tijdslot.dagnummer, tijdslot.baannummer, tijdslot.starttijd,
                     wedstrijd.baan AS echte_baan, wedstrijd.starttijd AS echte_start,
                     aanwezig_1_a, aanwezig_1_b, aanwezig_2_a, aanwezig_2_b, wedstrijd_status, wachtstarttijd,
                     set1_team1, set1_team2, set2_team1, set2_team2, set3_team1, set3_team2, winnaar, opgave,
                     wedstrijd_wijziging.actie, wedstrijd_wijziging.wijziging_status, wedstrijd_wijziging.id AS wedstrijd_wijziging_id,
                     speler_1_akkoord, partner_1_akkoord, speler_2_akkoord, partner_2_akkoord,
                     D1A.user_id AS speler1, D1A.naam AS naam1A, D1B.user_id AS partner1, D1B.naam AS naam1B,
                     D2A.user_id AS speler2, D2A.naam AS naam2A, D2B.user_id AS partner2, D2B.naam AS naam2B,
                     IN1.ranking_effectief AS ranking1, IN2.ranking_effectief AS ranking2,
                     IN1.deelnemerA AS bondsnr1A, IN1.deelnemerB AS bondsnr1B, IN2.deelnemerA AS bondsnr2A, IN2.deelnemerB AS bondsnr2B
                FROM combinations
                INNER JOIN categorie ON combinations.categorie = categorie.cat
                INNER JOIN tijdslot ON tijdslot=tijdslot.id
                INNER JOIN inschrijving IN1 ON IN1.id = combinations.deelnemer1
                INNER JOIN deelnemer D1A ON IN1.deelnemerA = D1A.bondsnummer
                INNER JOIN inschrijving IN2 ON IN2.id = combinations.deelnemer2
                INNER JOIN deelnemer D2A ON IN2.deelnemerA = D2A.bondsnummer
                LEFT OUTER JOIN deelnemer D1B ON IN1.deelnemerB = D1B.bondsnummer
                LEFT OUTER JOIN deelnemer D2B ON IN2.deelnemerB = D2B.bondsnummer
                LEFT OUTER JOIN wedstrijd ON combinations.id = wedstrijd.wedstrijd_id
                LEFT OUTER JOIN wedstrijd_wijziging ON wedstrijd_wijziging.wedstrijd_id = combinations.id AND wijziging_status != 'definitief'
                LEFT OUTER JOIN herplan_optie ON wedstrijd_wijziging.herplan_optie_id = herplan_optie.id
                WHERE combinations.toernooi_id = :toernooi_id
                    AND categorie.toernooi_id = :toernooi_id " . $where_clause . "
                ORDER BY tijdslot.dagnummer, tijdslot.starttijd, tijdslot.baannummer";

        $this->logger->info("toonWedstrijdenWhere, query: " . $query);
        return $this->mySQLDB->PDO_query_all($query, ['toernooi_id' => $toernooiId]);
    }

    public function toonDeelnemerWedstrijden(int $toernooiId, int $userId): false|array
    {
        $query =
            "SELECT combinations.id
             FROM combinations
                INNER JOIN inschrijving
                    ON inschrijving.id = combinations.deelnemer1
                    OR inschrijving.id = combinations.deelnemer2
                INNER JOIN deelnemer ON deelnemer.user_id = :user_id
              WHERE combinations.toernooi_id = :toernooi_id1
                AND inschrijving.toernooi_id = :toernooi_id2
                AND inschrijving.actief = 1
                AND (inschrijving.deelnemerA = deelnemer.bondsnummer OR inschrijving.deelnemerB = deelnemer.bondsnummer)
                AND combinations.tijdslot > 0";
        $result_array = $this->mySQLDB->PDO_query_all($query, ['user_id' => $userId, 'toernooi_id1' => $toernooiId, 'toernooi_id2' => $toernooiId]);

        $list = join("','", array_column($result_array, 'id'));
        $wedstrijden = $this->toonWedstrijdenWhere($toernooiId, "AND combinations.id IN ('$list') ");

        return ($wedstrijden);
    }

    public function toonDagWedstrijden(int $toernooiId, int $dagNummer): false|array
    {
        $this->logger->info("toonDagwedstrijden, dagnr: " . $dagNummer);
        return $this->toonWedstrijdenWhere($toernooiId, "AND tijdslot.dagnummer = '$dagNummer' ");
    }

    public function getWedstrijdGegevens(int $wedstrijdId): false|array
    {
        $query =
            "SELECT combinations.id AS wedstrijd_id, IN1.categorie, IN1.cat_type, combinations.tijdslot,
                 D1A.user_id AS speler1, D1A.naam AS naam_speler1, D1B.user_id AS partner1, D1B.naam AS naam_partner1,
                 D2A.user_id AS speler2, D2A.naam AS naam_speler2, D2B.user_id AS partner2, D2B.naam AS naam_partner2
            FROM combinations
            INNER JOIN inschrijving IN1 ON IN1.id = combinations.deelnemer1
            INNER JOIN deelnemer D1A ON IN1.deelnemerA = D1A.bondsnummer
            LEFT JOIN deelnemer D1B ON IN1.deelnemerB = D1B.bondsnummer
            LEFT JOIN inschrijving IN2 ON IN2.id = combinations.deelnemer2
            LEFT JOIN deelnemer D2A ON IN2.deelnemerA = D2A.bondsnummer
            LEFT JOIN deelnemer D2B ON IN2.deelnemerB = D2B.bondsnummer
            WHERE combinations.id = :wedstrijd_id";
        return $this->mySQLDB->PDO_query_first($query, ["wedstrijd_id" => $wedstrijdId]);
    }

    /**
     * Een aantal functies om het herplannen van wedstrijden te ondersteunen
     * getWedstrijden() haalt alle wedstrijden op van de deelnemers voor een wedstrijd
     */
    public function getWedstrijden(int $toernooiId, string $bondsnummers): false|array
    {
        $query =
            "SELECT 
                combinations.id AS wedstrijd_id,
                tijdslot.id AS tijdslot_id,
                tijdslot.dagnummer,
                tijdslot.starttijd,
                user_id,
                naam,
                wedstrijd_duur,
                bondsnummer
            FROM
                combinations
            INNER JOIN inschrijving
                ON inschrijving.id = combinations.deelnemer1
                OR inschrijving.id = combinations.deelnemer2
            INNER JOIN deelnemer
                ON inschrijving.deelnemerA = deelnemer.bondsnummer
                OR inschrijving.deelnemerB = deelnemer.bondsnummer
            INNER JOIN tijdslot
                ON combinations.tijdslot = tijdslot.id
            INNER JOIN speeltijden
                ON speeltijden.toernooi_id = :toernooi_id1
                AND tijdslot.dagnummer = speeltijden.dagnummer
            WHERE
                combinations.toernooi_id = :toernooi_id2
                AND bondsnummer IN ($bondsnummers)
                AND inschrijving.actief = 1
            ORDER BY tijdslot.dagnummer, tijdslot.starttijd";

        //$this->logger->info("getWedstrijden, query: " . $query);

        return $this->mySQLDB->PDO_query_all($query, ['toernooi_id1' => $toernooiId, 'toernooi_id2' => $toernooiId]);
    }

    /**
     * getBondsnummers() is een hulpfunctie, het haalt de bondsnummers voor een wedstrijd op
     * en retourneert ze in een string van bondsnummers, gescheiden door komma's.
     * Dit is bedoeld om deze string te kunnen gebruiken in een SELECT .. WHERE bondsnummer in ($bondsnummers)
     */
    public function getBondsnummers(int $wedstrijdId): string
    {
        $query =
            "SELECT bondsnummer
             FROM combinations
             INNER JOIN inschrijving 
                ON inschrijving.id = combinations.deelnemer1
                OR inschrijving.id = combinations.deelnemer2
             INNER JOIN deelnemer
                ON inschrijving.deelnemerA = deelnemer.bondsnummer
                OR inschrijving.deelnemerB = deelnemer.bondsnummer
             WHERE
                combinations.id = :wedstrijd_id
                AND inschrijving.actief = 1";
        $result_array = $this->mySQLDB->PDO_query_all($query, ["wedstrijd_id" => $wedstrijdId]);

        $bondsnummers = join(", ", array_column($result_array, 'bondsnummer'));
        return $bondsnummers;
    }

    /**
     * getUserIds() haalt de user_ids voor een wedstrijd op
     */
    public function getUserIds(int $wedstrijdId): false|array
    {
        $query =
            "SELECT user_id
            FROM combinations
                INNER JOIN inschrijving
                    ON inschrijving.id = combinations.deelnemer1
                    OR inschrijving.id = combinations.deelnemer2
                INNER JOIN deelnemer
                    ON inschrijving.deelnemerA = deelnemer.bondsnummer
                    OR inschrijving.deelnemerB = deelnemer.bondsnummer
            WHERE
                combinations.id = :wedstrijd_id
                AND inschrijving.actief = 1";
        return $this->mySQLDB->PDO_query_all($query, ["wedstrijd_id" => $wedstrijdId]);
    }

    public function AddCombination(int $toernooi_id, string $categorie, int $inschrijving_id_1, int $inschrijving_id_2): int
    {
        // Check of deze combinatie al bestaat:
        $query =
            "SELECT id, tijdslot
            FROM combinations 
            WHERE 
                (deelnemer1 = :inschrijving_id_1A AND deelnemer2 = :inschrijving_id_2A)
                 OR (deelnemer1 = :inschrijving_id_2B AND deelnemer2 = :inschrijving_id_1B)";
        $result = $this->mySQLDB->PDO_query_first(
            $query,
            [
                'inschrijving_id_1A' => $inschrijving_id_1,
                'inschrijving_id_2A' => $inschrijving_id_2,
                'inschrijving_id_1B' => $inschrijving_id_1,
                'inschrijving_id_2B' => $inschrijving_id_2
            ]);
        if ($result) {
            // Geef het gevonden combination_id terug, of een foutcode -1 als er al een wedstrijd gepland is voor deze combinatie
            return ($result['tijdslot'] > 0 ? -1 : $result['id']);
        }

        // Geen bestaande combinatie gevonden, maak een nieuwe combinatie:
        $combination = new Combinations();
        $combination->setToernooiId($toernooi_id);
        $combination->setCategorie($categorie);
        $combination->setDeelnemer1($inschrijving_id_1);
        $combination->setDeelnemer2($inschrijving_id_2);
        $combination->setPlanRonde(0);
        $combination->setTijdslot(-1);

        $this->getEntityManager()->persist($combination);
        $this->getEntityManager()->flush();

        return $combination->getId() ?? -2;
    }

    public function getCategorieGeplandTotalen(int $toernooiId): false|array
    {
        $query =
            "SELECT 
                cat,
                COUNT(categorie) AS gepland_aantal
            FROM combinations
                RIGHT OUTER JOIN categorie ON categorie = cat
            WHERE
                combinations.toernooi_id = :toernooi_id1
                AND categorie.toernooi_id = :toernooi_id2
                AND tijdslot > 0
            GROUP BY cat
            ORDER BY cat";

        return $this->mySQLDB->PDO_query_all($query, ['toernooi_id1' => $toernooiId, 'toernooi_id2' => $toernooiId]);
    }
}
