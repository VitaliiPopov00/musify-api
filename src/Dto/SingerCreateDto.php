<?php

namespace App\Dto;

use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as AppAssert;

class SingerCreateDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Name is required')]
        #[Assert\Length(
            max: 60,
            maxMessage: 'Name is too long'
        )]
        #[AppAssert\UniqueSingerName]
        public string $name,

        #[Assert\Type(type: Types::STRING)]
        public ?string $description = null,

        #[Assert\NotBlank(message: 'Genres is required')]
        #[Assert\Count(
            min: 1,
            minMessage: 'At least one genre is required',
            max: 5,
            maxMessage: 'Maximum of 5 genres',
        )]
        #[Assert\All([
            new Assert\NotBlank,
            new Assert\Type(type: Types::STRING),
            new Assert\Length(max: 60)
        ])]
        #[AppAssert\GenresExist]
        public array $genres = [],
    )
    {
    }
}