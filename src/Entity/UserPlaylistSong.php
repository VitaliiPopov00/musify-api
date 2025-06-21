<?php

namespace App\Entity;

use App\Repository\UserPlaylistSongRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserPlaylistSongRepository::class)]
#[ORM\Table(name: 'user_playlist_song')]
class UserPlaylistSong
{
    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'songs')]
        #[ORM\JoinColumn(name: 'user_playlist_id', nullable: false)]
        private ?UserPlaylist $playlist = null,

        #[ORM\ManyToOne]
        #[ORM\JoinColumn(name: 'song_id', nullable: false)]
        private ?Song $song = null,

        #[ORM\Column]
        private ?\DateTimeImmutable $createdAt = new \DateTimeImmutable(),

        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column]
        private ?int $id = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlaylist(): ?UserPlaylist
    {
        return $this->playlist;
    }

    public function setPlaylist(?UserPlaylist $playlist): self
    {
        $this->playlist = $playlist;
        return $this;
    }

    public function getSong(): ?Song
    {
        return $this->song;
    }

    public function setSong(?Song $song): self
    {
        $this->song = $song;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
} 