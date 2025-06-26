<?php

namespace App\Repository;

use App\Entity\Genre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Genre>
 */
class GenreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Genre::class);
    }

    public function findOneByTitleIgnoreCase(string $title): ?Genre
    {
        $qb = $this->createQueryBuilder('g');
        
        return $qb->where('LOWER(g.title) = LOWER(:title)')
            ->setParameter('title', $title)
            ->getQuery()
            ->getOneOrNullResult();
    }
}