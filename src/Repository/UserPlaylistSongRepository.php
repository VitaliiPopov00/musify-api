<?php

namespace App\Repository;

use App\Entity\UserPlaylistSong;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserPlaylistSong>
 *
 * @method UserPlaylistSong|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserPlaylistSong|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserPlaylistSong[]    findAll()
 * @method UserPlaylistSong[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserPlaylistSongRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPlaylistSong::class);
    }

    /**
     * @return UserPlaylistSong[]
     */
    public function findByPlaylist(int $playlistId): array
    {
        return $this->createQueryBuilder('ps')
            ->andWhere('ps.playlist = :playlistId')
            ->setParameter('playlistId', $playlistId)
            ->orderBy('ps.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByPlaylistAndSong(int $playlistId, int $songId): ?UserPlaylistSong
    {
        return $this->createQueryBuilder('ps')
            ->andWhere('ps.playlist = :playlistId')
            ->andWhere('ps.song = :songId')
            ->setParameter('playlistId', $playlistId)
            ->setParameter('songId', $songId)
            ->getQuery()
            ->getOneOrNullResult();
    }
} 