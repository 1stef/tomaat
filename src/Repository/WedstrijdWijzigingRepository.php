<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\WedstrijdWijziging;
use App\Service\MySQLDB;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WedstrijdWijziging|null find($id, $lockMode = null, $lockVersion = null)
 * @method WedstrijdWijziging|null findOneBy(array $criteria, array $orderBy = null)
 * @method WedstrijdWijziging[]    findAll()
 * @method WedstrijdWijziging[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WedstrijdWijzigingRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly MySQLDB $mySQLDB)
    {
        parent::__construct($registry, WedstrijdWijziging::class);
    }

    /**
     * getWedstrijdWijzigingen haalt alle nog niet door alle spelers bevestigde wedstrijdwijzigingen voor een toernooi op,
     * verrijkt met o.a. deelnemer gegevens, voor een toernooi_administrator.
     */
    function getWedstrijdWijzigingen(int $toernooiId): false|array
    {
        $query =
            "SELECT 
                wedstrijd_id,
                wijziging_status,
                actie,
                categorie,
                cat_type,
                herplan_optie_id,
                tijdslot.dagnummer AS dagnummer_oud,
                tijdslot.starttijd AS starttijd_oud,
                herplan_optie.dagnummer AS dagnummer_nieuw,
                herplan_optie.starttijd AS starttijd_nieuw,
                speler1,
                partner1,
                speler2,
                partner2,
                D1S.naam AS naam_speler1,
                D1P.naam AS naam_partner1,
                D2S.naam AS naam_speler2,
                D2P.naam AS naam_partner2,
                D1S.telefoonnummer AS tel_speler1,
                D1P.telefoonnummer AS tel_partner1,
                D2S.telefoonnummer AS tel_speler2,
                D2P.telefoonnummer AS tel_partner2,
                speler_1_akkoord,
                partner_1_akkoord,
                speler_2_akkoord,
                partner_2_akkoord,
                IF(herplan_optie.dagnummer < tijdslot.dagnummer,
                    herplan_optie.dagnummer,
                    tijdslot.dagnummer) AS sorteerdagnr
            FROM
                wedstrijd_wijziging
                    INNER JOIN
                herplan_optie ON herplan_optie_id = herplan_optie.id
                    INNER JOIN
                tijdslot ON tijdslot_oud = tijdslot.id
                    INNER JOIN
                combinations ON combinations.id = wedstrijd_id
                    INNER JOIN
                deelnemer D1S ON D1S.user_id = speler1
                    LEFT JOIN
                deelnemer D1P ON D1P.user_id = partner1
                    INNER JOIN
                deelnemer D2S ON D2S.user_id = speler2
                    LEFT JOIN
                deelnemer D2P ON D2P.user_id = partner2
            WHERE
                wedstrijd_wijziging.toernooi_id = :toernooi_id
                    AND wijziging_status != 'definitief'
                    AND wijziging_status != 'nieuw'
            ORDER BY sorteerdagnr";

        return $this->mySQLDB->PDO_query_all($query, ['toernooi_id' => $toernooiId]);
    }

}
