<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Bericht;
use App\Service\MySQLDB;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Bericht|null find($id, $lockMode = null, $lockVersion = null)
 * @method Bericht|null findOneBy(array $criteria, array $orderBy = null)
 * @method Bericht[]    findAll()
 * @method Bericht[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BerichtRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly MySQLDB $mySQLDB)
    {
        parent::__construct($registry, Bericht::class);
    }

    /**
     * getOntvangenBerichten(): Geef alle ontvangen berichten voor een user voor een toernooi_id
     * Als toernooi_id == NULL, dan geef de ontvangen berichten voor alle toernooien
     * Geef alleen berichten voor toernooien met status inschrijven, plannen of spelen
     */
    function getOntvangenBerichten(int $userId, ?int $toernooiId): array
    {
        $query =
            "SELECT
                t.toernooi_naam,
                b.id,
                b.verzend_tijd,
                b.titel,
                d.naam AS van
             FROM bericht b
                INNER JOIN toernooi t ON b.toernooi_id = t.id
                INNER JOIN wedstrijd_wijziging ww ON b.wedstrijd_wijziging_id = ww.id
                LEFT JOIN deelnemer d ON b.afzender = d.user_id
                WHERE
                    (b.toernooi_id = :toernooi_id OR :alleToernooien)
                  AND
                    (t.toernooi_status = 'inschrijven'
                     OR t.toernooi_status = 'plannen'
                     OR t.toernooi_status = 'spelen')
                  AND
                    b.verzend_tijd IS NOT null
                  AND
                    (b.ontvanger_1 = :user_id1
                     OR b.ontvanger_2 = :user_id2
                     OR b.ontvanger_3 = :user_id3
                     OR b.ontvanger_4 = :user_id4
                     OR b.alle_deelnemers = true)
                ORDER BY b.toernooi_id, b.verzend_tijd DESC";

        return
            $this->mySQLDB->PDO_query_all(
                $query,
                [
                    'toernooi_id' => $toernooiId,
                    'alleToernooien' => ($toernooiId == null),
                    'user_id1' => $userId,
                    'user_id2' => $userId,
                    'user_id3' => $userId,
                    'user_id4' => $userId
                ]);
    }

    /**
     * getVerzondenBerichten(): Geef alle verzonden berichten voor een user voor een toernooi_id
     * Als toernooi_id == NULL, dan geef de verzonden berichten voor alle toernooien
     * Geef alleen berichten voor toernooien met status inschrijven, plannen of spelen
     */
    function getVerzondenBerichten(int $userId, ?int $toernooiId): array
    {
        $query =
            "SELECT toernooi_naam,
                    bericht.id,
                    ONTV1.naam AS ontvanger1,
                    ONTV2.naam AS ontvanger2,
                    ONTV3.naam AS ontvanger3,
                    ONTV4.naam AS ontvanger4,
                    verzend_tijd,
                    titel
            FROM bericht
                    INNER JOIN deelnemer ONTV1 ON ontvanger_1 = ONTV1.user_id
                    LEFT JOIN deelnemer ONTV2 ON ontvanger_2 = ONTV2.user_id
                    LEFT JOIN deelnemer ONTV3 ON ontvanger_3 = ONTV3.user_id
                    LEFT JOIN deelnemer ONTV4 ON ontvanger_4 = ONTV4.user_id
                    INNER JOIN toernooi ON toernooi_id = toernooi.id
                WHERE 
                    (toernooi_id = :toernooi_id OR :alleToernooien)
                  AND
                    (toernooi_status = 'inschrijven'
                     OR toernooi_status = 'plannen'
                     OR toernooi_status = 'spelen')
                  AND
                    verzend_tijd IS NOT NULL
                  AND
                    afzender = :user_id
                ORDER BY toernooi_id, verzend_tijd DESC";

        return
            $this->mySQLDB->PDO_query_all(
                $query,
                [
                    'toernooi_id' => $toernooiId,
                    'alleToernooien' => ($toernooiId == null),
                    'user_id' => $userId
                ]);
    }

    /**
     * getOntvangenTLBerichten(): Geef alle ontvangen berichten voor de toernooileiding van een toernooi met toernooi_id
     */
    function getOntvangenTLBerichten(int $toernooiId): false|array
    {
        $query =
            "SELECT toernooi_naam, bericht.id, verzend_tijd, titel, deelnemer.naam AS van
            FROM bericht
                INNER JOIN deelnemer ON bericht.afzender = deelnemer.user_id
                INNER JOIN toernooi ON toernooi_id = toernooi.id
            WHERE
                toernooi_id = :toernooi_id
                AND cc_toernooileiding = 1
                AND verzend_tijd IS NOT null
            ORDER BY verzend_tijd DESC";

        return $this->mySQLDB->PDO_query_all($query, ['toernooi_id' => $toernooiId]);
    }

    /**
     * getVerzondenTLBerichten(): Geef alle verzonden berichten van de toernooileiding van een toernooi met het toernooi_id
     */
    function getVerzondenTLBerichten(int $toernooiId): false|array
    {
        $query =
            "SELECT toernooi_naam, bericht.id, ONTV1.naam AS ontvanger1, ONTV2.naam AS ontvanger2,
                     ONTV3.naam AS ontvanger3, ONTV4.naam AS ontvanger4, verzend_tijd, titel
            FROM bericht
                INNER JOIN deelnemer ONTV1 ON ontvanger_1 = ONTV1.user_id
                LEFT JOIN deelnemer ONTV2 ON ontvanger_2 = ONTV2.user_id
                LEFT JOIN deelnemer ONTV3 ON ontvanger_3 = ONTV3.user_id
                LEFT JOIN deelnemer ONTV4 ON ontvanger_4 = ONTV4.user_id
                INNER JOIN toernooi ON toernooi_id = toernooi.id
            WHERE
                toernooi_id = :toernooi_id
                AND van_toernooileiding = 1
                AND verzend_tijd IS NOT NULL 
            ORDER BY verzend_tijd DESC";

        return $this->mySQLDB->PDO_query_all($query, ['toernooi_id' => $toernooiId]);
    }

    public function getBerichtVerrijkt(int $berichtId): false|array
    {
        $query =
            "SELECT bericht.toernooi_id, toernooi.toernooi_naam, toernooi.eerste_dag, combinations.categorie, IN1.cat_type, berichttype,
                     AFZ.naam AS van, afzender, D1A.user_id AS user1A, D1B.user_id AS user1B, D2A.user_id AS user2A, D2B.user_id AS user2B, cc_toernooileiding, alle_deelnemers, 
                     tekst, actie, wedstrijd_wijziging_id,
                     OUD.dagnummer AS dagnummer_oud, OUD.starttijd AS starttijd_oud, NW.dagnummer AS dagnummer_nieuw, NW.starttijd AS starttijd_nieuw,
                     D1A.naam AS naam1A, D1B.naam AS naam1B, D2A.naam AS naam2A, D2B.naam AS naam2B
            FROM bericht
                INNER JOIN toernooi ON bericht.toernooi_id = toernooi.id
                INNER JOIN wedstrijd_wijziging ON wedstrijd_wijziging_id = wedstrijd_wijziging.id
                INNER JOIN tijdslot OUD ON tijdslot_oud = OUD.id
                LEFT JOIN tijdslot NW ON tijdslot_nieuw = NW.id
                INNER JOIN combinations ON wedstrijd_wijziging.wedstrijd_id = combinations.id
                INNER JOIN inschrijving IN1 ON combinations.deelnemer1 = IN1.id
                INNER JOIN deelnemer D1A ON IN1.deelnemerA = D1A.bondsnummer
                LEFT JOIN deelnemer D1B ON IN1.deelnemerB = D1B.bondsnummer
                LEFT JOIN inschrijving IN2 ON combinations.deelnemer2 = IN2.id
                LEFT JOIN deelnemer D2A ON IN2.deelnemerA = D2A.bondsnummer
                LEFT JOIN deelnemer D2B ON IN2.deelnemerB = D2B.bondsnummer
                LEFT JOIN deelnemer AFZ ON AFZ.user_id = bericht.afzender
            WHERE bericht.id = :bericht_id";

        return $this->mySQLDB->PDO_query_first($query, ['bericht_id' => $berichtId]);
    }

}
