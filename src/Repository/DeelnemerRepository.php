<?php
declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Deelnemer;
use App\Service\MySQLDB;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Deelnemer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Deelnemer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Deelnemer[]    findAll()
 * @method Deelnemer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeelnemerRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly MySQLDB $mySQLDB)
    {
        parent::__construct($registry, Deelnemer::class);
    }

    /**
     * Haal alle contactgegevens op voor de deelnemers aan een toernooi.
     * Alleen aan te roepen door toernooi-admins voor dat toernooi.
     */
    public function getContactGegevens(int $toernooiId): false|array
    {
        $query =
            "SELECT DISTINCT naam, email, telefoonnummer, bondsnummer
             FROM deelnemer
                INNER JOIN inschrijving ON deelnemerA = bondsnummer
                INNER JOIN user ON user.id = user_id
             WHERE toernooi_id = :toernooi_id
             ORDER BY lower(naam)";
        return $this->mySQLDB->PDO_query_all($query, ["toernooi_id" => $toernooiId]);
    }

}
