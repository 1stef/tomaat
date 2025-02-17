<?php
declare(strict_types = 1);

namespace App\Repository;

use App\Entity\ToernooiAdmin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Service\MySQLDB;
use Psr\Log\LoggerInterface;


/**
 * @extends ServiceEntityRepository<ToernooiAdmin>
 *
 * @method ToernooiAdmin|null find($id, $lockMode = null, $lockVersion = null)
 * @method ToernooiAdmin|null findOneBy(array $criteria, array $orderBy = null)
 * @method ToernooiAdmin[]    findAll()
 * @method ToernooiAdmin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ToernooiAdminRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly MySQLDB $mySQLDB,
        private readonly LoggerInterface $logger)
    {
        parent::__construct($registry, ToernooiAdmin::class);
    }

    /**
     * IsUserToernooiAdmin($toernooi_id, $user_id)
     */
    function IsUserToernooiAdmin(int $toernooi_id, string $admin_email): bool
    {
        $query = "SELECT admin_email 
                    FROM toernooi_admin
                    JOIN toernooi ON toernooi.id = toernooi_admin.toernooi_id
                    WHERE toernooi_id = :toernooi_id
                    AND admin_email = :admin_email
                    AND toernooi.toernooi_status IN ('voorbereiden inschrijving', 'inschrijven', 'plannen', 'spelen')";
        $result = !empty($this->mySQLDB->PDO_query_first($query, ['toernooi_id' => $toernooi_id, 'admin_email' => $admin_email]));

        $this->logger->info("IsUserToernooiAdmin, result " . ($result ? "true" : "false") . ", toernooi_id: " . $toernooi_id . ", admin_email: " . $admin_email);

        return $result;
    }

}
