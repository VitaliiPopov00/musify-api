<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as AppAssert;

class RegisterDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Login is required')]
        #[Assert\Length(
            max: 255,
            maxMessage: 'Login is too long'
        )]
        #[AppAssert\UniqueUserLogin]
        public string $login,

        #[Assert\NotBlank(message: 'Password is required')]
        #[Assert\Length(
            min: 6,
            minMessage: 'Login is too short'
        )]
        public string $password
    )
    {
    }
}