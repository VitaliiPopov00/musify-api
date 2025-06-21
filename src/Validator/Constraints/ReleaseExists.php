<?php

namespace App\Validator\Constraints;

use App\Validator\ReleaseExistsValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ReleaseExists extends Constraint
{
    public string $message = 'Release with id "{{ releaseId }}" does not exist.';

    public function validatedBy(): string
    {
        return ReleaseExistsValidator::class;
    }
} 