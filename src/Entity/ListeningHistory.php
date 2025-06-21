<?php

namespace App\Entity;

use App\Repository\ListeningHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: ListeningHistoryRepository::class)]
#[ORM\Table(name: 'listening_history')]
class ListeningHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Song::class)]
    #[ORM\JoinColumn(name: 'song_id', referencedColumnName: 'id', nullable: false)]
    private Song $song;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $listenedAt;

    public function __construct()
    {
        $this->listenedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getSong(): Song
    {
        return $this->song;
    }

    public function setSong(Song $song): self
    {
        $this->song = $song;
        return $this;
    }

    public function getListenedAt(): DateTimeImmutable
    {
        return $this->listenedAt;
    }

    public function setListenedAt(DateTimeImmutable $listenedAt): self
    {
        $this->listenedAt = $listenedAt;
        return $this;
    }
} 