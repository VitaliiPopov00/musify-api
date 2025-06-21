<?php

namespace App\Validator;

use App\Dto\ReleaseCreateDto;
use App\Validator\Constraints\ValidReleaseTime;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidReleaseTimeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidReleaseTime) {
            throw new UnexpectedTypeException($constraint, ValidReleaseTime::class);
        }

        if (!$value instanceof ReleaseCreateDto) {
            return;
        }

        $date = $value->getDate();
        $time = $value->getTime();
        $now = new \DateTimeImmutable();

        if ($date->format('Y-m-d') === $now->format('Y-m-d')) {
            $currentTime = $now->format('H:i:s');
            $releaseTime = $time->format('H:i:s');

            if ($releaseTime <= $currentTime) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
            }
        }
    }
} 