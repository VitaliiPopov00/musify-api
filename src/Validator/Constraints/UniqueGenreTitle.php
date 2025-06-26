<?php

namespace App\Validator\Constraints;

use App\Validator\UniqueGenreTitleValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueGenreTitle extends Constraint
{
    public string $message = 'Genre with title "{{ value }}" already exists';

    public function validatedBy(): string
    {
        return UniqueGenreTitleValidator::class;
    }
} 