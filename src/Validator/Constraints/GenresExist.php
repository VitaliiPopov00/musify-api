<?php

namespace App\Validator\Constraints;

use App\Validator\GenresExistValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class GenresExist extends Constraint
{
    public string $message = 'The genre with id "{{ genre }}" does not exist.';

    public function validatedBy(): string
    {
        return GenresExistValidator::class;
    }
}