<?php

namespace App\Validator\Constraints;

use App\Validator\SongExistsValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class SongExists extends Constraint
{
    public string $message = 'Song is not found';

    public function validatedBy(): string
    {
        return SongExistsValidator::class;
    }
} 