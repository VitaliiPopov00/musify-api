<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class LoginDto
{
    public function __construct(
        #[Assert\NotBlank(message: "Login is required")]
        public string $login,

        #[Assert\NotBlank(message: "Password is required")]
        public string $password,
    )
    {
    }
}