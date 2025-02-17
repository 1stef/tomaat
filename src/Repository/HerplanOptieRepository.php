<?php
declare(strict_types = 1);

namespace App\Repository;

use App\Entity\HerplanOptie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HerplanOptie|null find($id, $lockMode = null, $lockVersion = null)
 * @method HerplanOptie|null findOneBy(array $criteria, array $orderBy = null)
 * @method HerplanOptie[]    findAll()
 * @method HerplanOptie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HerplanOptieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HerplanOptie::class);
    }
}
