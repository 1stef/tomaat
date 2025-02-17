<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tijdslot;
use App\Service\MySQLDB;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tijdslot>
 *
 * @method Tijdslot|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tijdslot|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tijdslot[]    findAll()
 * @method Tijdslot[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TijdslotRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly MySQLDB $mySQLDB)
    {
        parent::__construct($registry, Tijdslot::class);
    }

    public function verwijderToernooiTijdsloten(int $toernooi_id): void
    {
        $query = "DELETE from tijdslot
                  WHERE toernooi_id = :toernooi_id";
        $this->mySQLDB->PDO_execute($query, ["toernooi_id" => $toernooi_id]);
    }

    public function clearTijdsloten(int $toernooiId): void
    {
        $query = "UPDATE tijdslot SET vrij = 1
                  WHERE toernooi_id = :toernooi_id";
        $this->mySQLDB->PDO_execute($query, ['toernooi_id' => $toernooiId]);
    }

    public function getTijdsloten(int $toernooiId): false|array
    {
        $query = "SELECT id, dagnummer, baannummer, slotnummer, starttijd, vrij
                  FROM tijdslot
                  WHERE toernooi_id = :toernooi_id
                  ORDER BY dagnummer, slotnummer";
        return $this->mySQLDB->PDO_query_all($query, ['toernooi_id' => $toernooiId]);
    }

    public function toonTijdsloten($toernooiId): false|array
    {
        $query =
            "SELECT dagnummer, COUNT(*) AS aantal_tijdsloten, COUNT(IF(vrij=0, 1, NULL)) AS aantal_bezet
             FROM tijdslot
             WHERE toernooi_id = :toernooi_id
             GROUP BY dagnummer
             ORDER BY dagnummer";
        return $this->mySQLDB->PDO_query_all($query, ['toernooi_id' => $toernooiId]);
    }

    /**
     * Haal alle vrije tijdsloten van een toernooi op, gegroepeerd per dag/tijd combinatie
     * Per dag/tijd combinatie wordt ook de eerste vrije tijdslot_id teruggegeven
     */
    public function getVrijeTijdsloten($toernooiId): false|array
    {
        $query =
            "SELECT 
                ANY_VALUE(tijdslot.id) AS eerste_tijdslot_id,
                ANY_VALUE(tijdslot.dagnummer) AS dagnummer,
                ANY_VALUE(tijdslot.starttijd) AS starttijd,
                ANY_VALUE(tijdslot.eindtijd) AS eindtijd,
                ANY_VALUE(wedstrijd_duur) AS wedstrijd_duur
            FROM
                tijdslot
                    INNER JOIN
                speeltijden ON tijdslot.toernooi_id = speeltijden.toernooi_id
            WHERE
                tijdslot.toernooi_id = :toernooi_id AND vrij = 1
            GROUP BY tijdslot.dagnummer , tijdslot.starttijd
            ORDER BY tijdslot.dagnummer , tijdslot.starttijd";
        return $this->mySQLDB->PDO_query_all($query, ['toernooi_id' => $toernooiId]);
    }

}
