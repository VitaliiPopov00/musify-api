<?php

namespace App\Service;

use App\Dto\ReleaseCreateDto;
use App\Entity\Release;
use App\Entity\ReleaseSinger;
use App\Entity\Singer;
use Doctrine\ORM\EntityManagerInterface;

class ReleaseService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function create(ReleaseCreateDto $dto, int $userId): Release
    {
        $release = (new Release())
            ->setTitle($dto->getTitle())
            ->setDate(new \DateTime($dto->getDate()->format('Y-m-d')))
            ->setTime(new \DateTime($dto->getTime()->format('H:i:s')))
            ->setIsReleased($dto->getStraight())
            ->setCreatedBy($userId);

        $singerRepository = $this->entityManager->getRepository(Singer::class);

        foreach ($dto->getSingers() as $singerId) {
            $singer = $singerRepository->find($singerId);
            if ($singer) {
                $releaseSinger = new ReleaseSinger();
                $releaseSinger->setSinger($singer);
                $release->addReleaseSinger($releaseSinger);
            }
        }

        $this->entityManager->persist($release);
        $this->entityManager->flush();

        return $release;
    }

    public function get(int $id): ?Release
    {
        return $this->entityManager->getRepository(Release::class)->find($id);
    }

    public function getBySinger(int $singerId): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        return $qb->select('r')
            ->from(Release::class, 'r')
            ->join('r.releaseSingers', 'rs')
            ->join('rs.singer', 's')
            ->where('s.id = :singerId AND r.isReleased = 1')
            ->setParameter('singerId', $singerId)
            ->orderBy('r.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getBySingerFuture(int $singerId): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        return $qb->select('r')
            ->from(Release::class, 'r')
            ->join('r.releaseSingers', 'rs')
            ->join('rs.singer', 's')
            ->where('s.id = :singerId AND r.isReleased = 0')
            ->setParameter('singerId', $singerId)
            ->orderBy('r.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 