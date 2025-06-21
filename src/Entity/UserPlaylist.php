<?php

namespace App\Entity;

use App\Repository\UserPlaylistRepository;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserPlaylistRepository::class)]
#[ORM\Table(name: 'user_playlist')]
class UserPlaylist
{
    #[ORM\OneToMany(mappedBy: 'playlist', targetEntity: UserPlaylistSong::class, orphanRemoval: true)]
    private Collection $songs;

    public function __construct(
        #[ORM\Column(length: 60)]
        #[Assert\NotBlank]
        #[Assert\Length(max: 60)]
        private ?string $title = null,

        #[ORM\Column(type: Types::INTEGER)]
        private int $createdBy,

        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column]
        private ?int $id = null,

        #[ORM\Column]
        private ?\DateTimeImmutable $createdAt = new \DateTimeImmutable(),

        #[ORM\Column]
        private ?\DateTimeImmutable $updatedAt = new \DateTimeImmutable(),
    ) {
        $this->songs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getCreatedBy(
        UserRepository $userRepository
    ): ?User
    {
        return $userRepository->findOneBy(['id' => $this->createdBy]);
    }

    public function setCreatedBy(?int $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection<int, UserPlaylistSong>
     */
    public function getSongs(): Collection
    {
        return $this->songs;
    }

    public function addSong(UserPlaylistSong $song): self
    {
        if (!$this->songs->contains($song)) {
            $this->songs->add($song);
            $song->setPlaylist($this);
        }
        return $this;
    }

    public function removeSong(UserPlaylistSong $song): self
    {
        if ($this->songs->removeElement($song)) {
            if ($song->getPlaylist() === $this) {
                $song->setPlaylist(null);
            }
        }
        return $this;
    }
} 