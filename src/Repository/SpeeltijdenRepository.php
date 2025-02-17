<?php
declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Speeltijden;
use App\Service\MySQLDB;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Speeltijden|null find($id, $lockMode = null, $lockVersion = null)
 * @method Speeltijden|null findOneBy(array $criteria, array $orderBy = null)
 * @method Speeltijden[]    findAll()
 * @method Speeltijden[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SpeeltijdenRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly MySQLDB $mySQLDB)
    {
        parent::__construct($registry, Speeltijden::class);
    }

    /**
     * @return Speeltijden[] Returns an array of Speeltijden objects
     */

    public function findByToernooiId(int $toernooiId): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.toernooi_id = :toernooi_id')
            ->setParameter('toernooi_id', $toernooiId)
            ->orderBy('b.dagnummer', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getSpeeltijden(int $toernooiId): false|array
    {
        $query = "SELECT id, dagnummer, wedstrijd_duur, starttijd, eindtijd FROM speeltijden
                 WHERE toernooi_id = :toernooi_id";
        return $this->mySQLDB->PDO_query_all($query, ["toernooi_id" => $toernooiId]);
    }
}
