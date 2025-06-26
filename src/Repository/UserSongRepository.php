<?php

namespace App\Repository;

use App\Entity\UserSong;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserSongRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSong::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('us')
            ->leftJoin('us.song', 's')
            ->addSelect('s')
            ->where('us.user = :user')
            ->setParameter('user', $user)
            ->orderBy('us.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByUserAndSong(User $user, int $songId): ?UserSong
    {
        return $this->createQueryBuilder('us')
            ->where('us.user = :user')
            ->andWhere('us.song = :songId')
            ->setParameter('user', $user)
            ->setParameter('songId', $songId)
            ->getQuery()
            ->getOneOrNullResult();
    }
} 