<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class DeleteCustomGenreDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Title is required')]
        #[Assert\Length(
            min: 1,
            max: 255,
            minMessage: 'Title cannot be empty',
            maxMessage: 'Title is too long'
        )]
        public string $title
    )
    {
    }
} 