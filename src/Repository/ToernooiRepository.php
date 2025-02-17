<?php
declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Toernooi;
use App\Service\MySQLDB;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Toernooi|null find($id, $lockMode = null, $lockVersion = null)
 * @method Toernooi|null findOneBy(array $criteria, array $orderBy = null)
 * @method Toernooi[]    findAll()
 * @method Toernooi[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ToernooiRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly MySQLDB $mySQLDB)
    {
        parent::__construct($registry, Toernooi::class);
    }

    /*
     * getToernooien - geef alle toernooien die zichtbaar moeten zijn voor spelers.
     * Retourneert array met id, toernooi_naam en toernooi_status voor ieder toernooi
     */
    public function getToernooien(): false|array
    {
        $query = "SELECT id, toernooi_naam, toernooi_status
                    FROM toernooi
                    WHERE toernooi_status IN ('inschrijven', 'plannen', 'spelen')";
        return $this->mySQLDB->PDO_query_all($query);
    }

    /**
     * getToernooienVoorAanvrager - Geef een array met alle toernooien voor een aanvrager terug
     * met bepaalde statussen in de database.
     */
    public function getToernooienVoorAanvrager(int $aanvrager_id): false|array
    {
        $query = "SELECT id, toernooi_naam, toernooi_status
                    FROM toernooi
                    WHERE (admin_id = :aanvrager_id) AND (toernooi_status != 'afgesloten')";
        return $this->mySQLDB->PDO_query_all($query, [':aanvrager_id' => $aanvrager_id]);
    }

    /**
     * getToernooienVoorAdmin - Geef een array met alle toernooien voor een toernooi admin terug
     * met bepaalde statussen in de database.
     */
    public function getToernooienVoorAdmin(string $admin_email): false|array
    {
        $query = "SELECT toernooi.id, toernooi_naam, toernooi_status
                    FROM toernooi
                    JOIN toernooi_admin ON toernooi.id = toernooi_admin.toernooi_id
                    WHERE toernooi_admin.admin_email = :admin_email
                    AND toernooi.toernooi_status IN ('voorbereiden inschrijving', 'inschrijven', 'plannen', 'spelen')";
        return $this->mySQLDB->PDO_query_all($query, ['admin_email' => $admin_email]);
    }

    /*
     * getToernooiDagen - geeft een array met de dagnummers voor een toernooi
     */
    public function getToernooiDagen(int $toernooi_id): array
    {
        $toernooi = $this->find($toernooi_id);

        $dagen = array();
        for ($dagnr = 1; $dagnr <= $toernooi->getAantalDagen(); $dagnr++) {
            array_push($dagen, $dagnr);
        }

        return $dagen;
    }
}
