<?php

namespace App\Dto;

use App\Validator\Constraints\SingersExist;
use App\Validator\Constraints\ValidReleaseTime;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidReleaseTime]
class ReleaseCreateDto
{
    #[Assert\NotBlank(message: 'Title cannot be empty')]
    private string $title;

    #[Assert\GreaterThanOrEqual('today', message: 'Date must be today or later')]
    private \DateTimeImmutable $date;

    private \DateTimeImmutable $time;

    #[SingersExist]
    private array $singers;

    private bool $straight;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getTime(): \DateTimeImmutable
    {
        return $this->time;
    }

    public function setTime(\DateTimeImmutable $time): self
    {
        $this->time = $time;
        return $this;
    }

    public function getSingers(): array
    {
        return $this->singers;
    }

    public function setSingers(array $singers): self
    {
        $this->singers = $singers;
        return $this;
    }

    public function getStraight(): bool
    {
        return $this->straight;
    }

    public function setStraight(bool $straight): self
    {
        $this->straight = $straight;

        return $this;
    }
} 