<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByApiToken(string $token): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.apiToken = :token')
            ->setParameter(
                key: 'token',
                value: $token
            )
            ->getQuery()
            ->getOneOrNullResult();
    }
}
