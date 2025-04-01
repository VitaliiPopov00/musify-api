<?php

namespace App\Validator\Constraints;

use App\Validator\UniqueUserLoginValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueUserLogin extends Constraint
{
    public string $message = 'Login "{{ value }}" is already in registered';

    public function validatedBy(): string
    {
        return UniqueUserLoginValidator::class;
    }
}
