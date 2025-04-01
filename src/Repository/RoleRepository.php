<?php
// src/Repository/RoleRepository.php

namespace App\Repository;

use App\Entity\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Role>
 */
class RoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    public function getUserRole(): Role|null
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.title = :title')
            ->setParameter(
                key: 'title',
                value: 'user'
            )
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
