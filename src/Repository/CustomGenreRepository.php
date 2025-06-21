<?php

namespace App\Repository;

use App\Entity\CustomGenre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomGenre>
 *
 * @method CustomGenre|null find($id, $lockMode = null, $lockVersion = null)
 * @method CustomGenre|null findOneBy(array $criteria, array $orderBy = null)
 * @method CustomGenre[]    findAll()
 * @method CustomGenre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomGenreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomGenre::class);
    }

    public function findByEntityType(string $entityType): array
    {
        return $this->createQueryBuilder('cg')
            ->andWhere('cg.entityType = :entityType')
            ->setParameter('entityType', $entityType)
            ->orderBy('cg.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByEntity(int $entityId, string $entityType): ?CustomGenre
    {
        return $this->createQueryBuilder('cg')
            ->andWhere('cg.entityId = :entityId')
            ->andWhere('cg.entityType = :entityType')
            ->setParameter('entityId', $entityId)
            ->setParameter('entityType', $entityType)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('cg')
            ->andWhere('cg.createdBy = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('cg.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByTitle(string $title): ?CustomGenre
    {
        return $this->createQueryBuilder('cg')
            ->andWhere('cg.title = :title')
            ->setParameter('title', $title)
            ->getQuery()
            ->getOneOrNullResult();
    }
} 