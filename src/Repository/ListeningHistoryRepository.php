<?php

namespace App\Repository;

use App\Entity\ListeningHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ListeningHistory>
 *
 * @method ListeningHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ListeningHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ListeningHistory[]    findAll()
 * @method ListeningHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ListeningHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListeningHistory::class);
    }
} 