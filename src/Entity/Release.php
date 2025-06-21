<?php

namespace App\Entity;

use App\Repository\ReleaseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReleaseRepository::class)]
#[ORM\Table(name: 'releases')]
class Release
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'date')]
    private \DateTime $date;

    #[ORM\Column(type: 'time')]
    private \DateTime $time;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column]
    private int $createdBy;

    #[ORM\Column]
    private bool $isReleased;

    #[ORM\OneToMany(mappedBy: 'release', targetEntity: ReleaseSinger::class, cascade: ['persist'])]
    private Collection $releaseSingers;

    #[ORM\OneToMany(mappedBy: 'release', targetEntity: ReleaseSong::class, cascade: ['persist'])]
    private Collection $releaseSongs;

    public function __construct()
    {
        $this->releaseSingers = new ArrayCollection();
        $this->releaseSongs = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getTime(): \DateTime
    {
        return $this->time;
    }

    public function setTime(\DateTime $time): self
    {
        $this->time = $time;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(int $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getReleaseSingers(): Collection
    {
        return $this->releaseSingers;
    }

    public function addReleaseSinger(ReleaseSinger $releaseSinger): self
    {
        if (!$this->releaseSingers->contains($releaseSinger)) {
            $this->releaseSingers->add($releaseSinger);
            $releaseSinger->setRelease($this);
        }
        return $this;
    }

    public function getReleaseSongs(): Collection
    {
        return $this->releaseSongs;
    }

    public function addReleaseSong(ReleaseSong $releaseSong): self
    {
        if (!$this->releaseSongs->contains($releaseSong)) {
            $this->releaseSongs->add($releaseSong);
            $releaseSong->setRelease($this);
        }
        return $this;
    }

    public function getIsReleased(): int
    {
        return $this->isReleased;
    }

    public function setIsReleased(bool $straight): self
    {
        $this->isReleased = (int) $straight;

        return $this;
    }
} 