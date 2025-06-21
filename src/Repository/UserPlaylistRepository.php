<?php

namespace App\Repository;

use App\Entity\UserPlaylist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserPlaylist>
 *
 * @method UserPlaylist|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserPlaylist|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserPlaylist[]    findAll()
 * @method UserPlaylist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserPlaylistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPlaylist::class);
    }

    /**
     * @return UserPlaylist[]
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.createdBy = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByUserAndId(int $userId, int $playlistId): ?UserPlaylist
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.createdBy = :userId')
            ->andWhere('p.id = :playlistId')
            ->setParameter('userId', $userId)
            ->setParameter('playlistId', $playlistId)
            ->getQuery()
            ->getOneOrNullResult();
    }
} 