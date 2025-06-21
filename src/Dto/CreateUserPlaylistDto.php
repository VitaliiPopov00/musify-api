<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateUserPlaylistDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 60)]
        private ?string $title = null,
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }
} 