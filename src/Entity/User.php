<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    private string $login;

    #[ORM\Column(type: Types::STRING)]
    private string $password;

    #[ORM\ManyToOne(
        targetEntity: Role::class,
        inversedBy: 'users'
    )]
    #[ORM\JoinColumn(
        name: 'role_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private Role $role;

    #[ORM\OneToOne(
        mappedBy: 'user',
        targetEntity: Singer::class,
    )]
    private ?Singer $singer = null;

    #[ORM\Column(
        type: Types::STRING,
        nullable: true
    )]
    private ?string $token = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: FavoriteSong::class, orphanRemoval: true)]
    private Collection $favoriteSongs;

    public function __construct()
    {
        $this->favoriteSongs = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(string $login): static
    {
        $this->login = $login;

        return $this;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function generateToken(): static
    {
        $this->token = bin2hex(random_bytes(32));

        return $this;
    }

    public function clearToken(): static
    {
        $this->token = null;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->login;
    }

    public function getRoles(): array
    {
        return (array) $this->role->getTitle();
    }

    public function eraseCredentials()
    {
    }

    public function getSinger(): ?Singer
    {
        return $this->singer;
    }

    public function setSinger(?Singer $singer): static
    {
        $this->singer = $singer;

        return $this;
    }

    /**
     * @return Collection<int, FavoriteSong>
     */
    public function getFavoriteSongs(): Collection
    {
        return $this->favoriteSongs;
    }

    public function addFavoriteSong(FavoriteSong $favoriteSong): static
    {
        if (!$this->favoriteSongs->contains($favoriteSong)) {
            $this->favoriteSongs->add($favoriteSong);
            $favoriteSong->setUser($this);
        }

        return $this;
    }

    public function removeFavoriteSong(FavoriteSong $favoriteSong): static
    {
        if ($this->favoriteSongs->removeElement($favoriteSong)) {
            if ($favoriteSong->getUser() === $this) {
                $favoriteSong->setUser(null);
            }
        }

        return $this;
    }

    public function getIsAdmin(): bool
    {
        return $this->getRole()->getId() === 1;
    }
}
