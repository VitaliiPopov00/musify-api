<?php

namespace App\Repository;

use App\Entity\FavoriteSong;
use App\Entity\Song;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FavoriteSongRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FavoriteSong::class);
    }

    public function findByUserAndSong(User $user, Song $song): ?FavoriteSong
    {
        return $this->findOneBy([
            'user' => $user,
            'song' => $song,
        ]);
    }
} 