<?php

namespace App\Entity;

use App\Repository\ReleaseSingerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReleaseSingerRepository::class)]
#[ORM\Table(name: 'release_singer')]
class ReleaseSinger
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Release::class, inversedBy: 'releaseSingers')]
    #[ORM\JoinColumn(nullable: false)]
    private Release $release;

    #[ORM\ManyToOne(targetEntity: Singer::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Singer $singer;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRelease(): Release
    {
        return $this->release;
    }

    public function setRelease(Release $release): self
    {
        $this->release = $release;
        return $this;
    }

    public function getSinger(): Singer
    {
        return $this->singer;
    }

    public function setSinger(Singer $singer): self
    {
        $this->singer = $singer;
        return $this;
    }
} 