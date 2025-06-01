<?php

namespace App\Validator\Constraints;

use App\Validator\UniqueSingerNameValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueSingerName extends Constraint
{
    public string $message = 'Artist name "{{ value }}" is already exists';

    public function validatedBy(): string
    {
        return UniqueSingerNameValidator::class;
    }
}
