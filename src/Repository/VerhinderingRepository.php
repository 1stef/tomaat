<?php
declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Verhindering;
use App\Service\MySQLDB;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @method Verhindering|null find($id, $lockMode = null, $lockVersion = null)
 * @method Verhindering|null findOneBy(array $criteria, array $orderBy = null)
 * @method Verhindering[]    findAll()
 * @method Verhindering[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VerhinderingRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly MySQLDB $mysqlDB,
        private readonly LoggerInterface $logger)
    {
        parent::__construct($registry, Verhindering::class);
    }

    public function verwijderVerhinderingen(int $toernooiId, int $bondsnummer): void
    {
        $this->logger->info("verwijderVerhinderingen, toernooi_id: " . $toernooiId . ", bondsnummer: " . $bondsnummer);
        $query =
            "DELETE from tijdslot
             WHERE
                toernooi_id = :toernooi_id
                AND bondsnr = :bondsnummer";
        $this->mysqlDB->PDO_execute($query, ['toernooi_id' => $toernooiId, 'bondsnummer' => $bondsnummer]);
    }

    public function verhinderingenMetInschrijving(int $toernooiId): false|array
    {
        $query =
            "SELECT verhindering.bondsnr, verhindering.dagnummer, verhindering.begintijd, verhindering.eindtijd,
                    inschrijving.id AS inschrijving_id
             FROM verhindering
               LEFT OUTER JOIN inschrijving
                    ON verhindering.bondsnr = inschrijving.deelnemerA 
                    OR verhindering.bondsnr = inschrijving.deelnemerB
             WHERE verhindering.toernooi_id = :toernooi_id1
               AND inschrijving.toernooi_id = :toernooi_id2
               AND inschrijving.actief = 1";

        return $this->mysqlDB->PDO_query_all($query, ['toernooi_id1' => $toernooiId, 'toernooi_id2' => $toernooiId]);
    }

    /**
     * getVerhinderingen: haal de verhinderingen voor een toernooi en deelnemer op
     */
    public function getVerhinderingen(int $toernooiId, int $bondsnummer): false|array
    {
        $query =
            "SELECT id, dagnummer, celnummer, begintijd, eindtijd, hele_dag FROM verhindering 
             WHERE toernooi_id = :toernooi_id
                AND bondsnr = :bondsnr
             ORDER BY dagnummer, begintijd";
        return $this->mysqlDB->PDO_query_all($query, ['toernooi_id' => $toernooiId, 'bondsnr' => $bondsnummer]);
    }

    /**
     * getAlleVerhinderingen() haalt alle verhinderingen op van de deelnemers voor een wedstrijd
     */
    public function getAlleVerhinderingen(int $toernooiId, string $bondsnummers): false|array
    {
        $query =
            "SELECT dagnummer, begintijd, eindtijd, bondsnr, naam, deelnemer.user_id
            FROM verhindering
                INNER JOIN deelnemer
                    ON verhindering.bondsnr = deelnemer.bondsnummer
            WHERE
                toernooi_id = :toernooi_id
                AND bondsnr IN ($bondsnummers)
            ORDER BY dagnummer, begintijd";
        return $this->mysqlDB->PDO_query_all($query, ['toernooi_id' => $toernooiId]);
    }
}
