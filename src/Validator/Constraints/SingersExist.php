<?php

namespace App\Validator\Constraints;

use App\Validator\SingersExistValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class SingersExist extends Constraint
{
    public string $message = 'One or more singers do not exist';

    public function validatedBy(): string
    {
        return SingersExistValidator::class;
    }
} 