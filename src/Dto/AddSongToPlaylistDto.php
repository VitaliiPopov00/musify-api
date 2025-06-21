<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as AppAssert;

#[AppAssert\SongNotInPlaylist(properties: ['songId', 'playlistId'])]
class AddSongToPlaylistDto
{
    #[Assert\NotBlank(message: 'Song ID cannot be empty')]
    #[Assert\Type(type: 'integer', message: 'Song ID must be a number')]
    #[AppAssert\SongExists]
    private ?int $songId = null;

    #[Assert\NotBlank(message: 'Playlist ID cannot be empty')]
    #[Assert\Type(type: 'integer', message: 'Playlist ID must be a number')]
    #[AppAssert\PlaylistExists]
    private ?int $playlistId = null;

    public function getSongId(): ?int
    {
        return $this->songId;
    }

    public function setSongId(?int $songId): self
    {
        $this->songId = $songId;
        return $this;
    }

    public function getPlaylistId(): ?int
    {
        return $this->playlistId;
    }

    public function setPlaylistId(?int $playlist): self
    {
        $this->playlistId = $playlist;
        return $this;
    }
}
