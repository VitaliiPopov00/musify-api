<?php

namespace App\Validator;

use App\Repository\SongRepository;
use App\Validator\Constraints\SongExists;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SongExistsValidator extends ConstraintValidator
{
    public function __construct(private readonly SongRepository $songRepository)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof SongExists) {
            throw new UnexpectedTypeException($constraint, SongExists::class);
        }

        $song = $this->songRepository->findOneBy(['id' => $value]);

        if (!$song) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
