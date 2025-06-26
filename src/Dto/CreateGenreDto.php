<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as AppAssert;

class CreateGenreDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Genre title is required')]
        #[Assert\Length(
            min: 1,
            max: 255,
            minMessage: 'Genre title cannot be empty',
            maxMessage: 'Genre title is too long'
        )]
        #[AppAssert\UniqueGenreTitle]
        public string $title
    )
    {
    }
} 