<?php

namespace App\Entity;

use App\Repository\SongRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SongRepository::class)]
class Song
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $title = null;

    #[ORM\Column(type: Types::INTEGER, options: ["default" => 0], nullable: true)]
    private ?int $playCount = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToMany(
        targetEntity: Singer::class,
        mappedBy: 'songs'
    )]
    private Collection $singers;

    #[ORM\ManyToMany(
        targetEntity: Genre::class,
        inversedBy: 'songs'
    )]
    #[ORM\JoinTable(name: 'song_genre')]
    private Collection $genres;

    public function __construct()
    {
        $this->singers = new ArrayCollection();
        $this->genres = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getFilePath(): string
    {
        if (!$this->getId() || $this->getSingers()->isEmpty()) {
            throw new \RuntimeException('Cannot get file path for unsaved song or song without singer');
        }

        $singer = $this->getSingers()->first();
        return sprintf('uploads/%d/%d.mp3', $singer->getId(), $this->getId());
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getSingers(): Collection
    {
        return $this->singers;
    }

    public function addSinger(Singer $singer): self
    {
        if (!$this->singers->contains($singer)) {
            $this->singers->add($singer);
            $singer->addSong($this);
        }

        return $this;
    }

    public function removeSinger(Singer $singer): self
    {
        if ($this->singers->removeElement($singer)) {
            $singer->removeSong($this);
        }

        return $this;
    }

    public function getGenres(): Collection
    {
        return $this->genres;
    }

    public function addGenre(Genre $genre): self
    {
        if (!$this->genres->contains($genre)) {
            $this->genres->add($genre);
            $genre->addSong($this);
        }

        return $this;
    }

    public function removeGenre(Genre $genre): self
    {
        if ($this->genres->removeElement($genre)) {
            $genre->removeSong($this);
        }

        return $this;
    }

    public function getPlayCount(): ?int
    {
        return $this->playCount;
    }

    public function incrementPlayCount(): self
    {
        $this->playCount++;
        $this->updatedAt = new \DateTimeImmutable();
        
        return $this;
    }
}