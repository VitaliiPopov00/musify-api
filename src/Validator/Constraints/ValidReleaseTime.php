<?php

namespace App\Validator\Constraints;

use App\Validator\ValidReleaseTimeValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidReleaseTime extends Constraint
{
    public string $message = 'Time must be later than current time for today\'s date';

    public function validatedBy(): string
    {
        return ValidReleaseTimeValidator::class;
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
} 