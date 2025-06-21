<?php

namespace App\Dto;

use App\Validator\Constraints as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;

class CreateSubscribeDto
{
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    #[AppAssert\SingersExist]
    private int $singerId;

    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private int $userId;

    public function getSingerId(): int
    {
        return $this->singerId;
    }

    public function setSingerId(int $singerId): self
    {
        $this->singerId = $singerId;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }
} 