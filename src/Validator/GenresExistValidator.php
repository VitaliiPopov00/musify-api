<?php

namespace App\Validator;

use App\Repository\GenreRepository;
use App\Validator\Constraints\GenresExist;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class GenresExistValidator extends ConstraintValidator
{
    public function __construct(private GenreRepository $genreRepository)
    {
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof GenresExist) {
            throw new UnexpectedTypeException($constraint, GenresExist::class);
        }

        if (null === $value || [] === $value) {
            return;
        }

        foreach ($value as $genre) {
            if (!$this->genreRepository->findOneBy(['title' => $genre])) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ genre }}', $genre)
                    ->addViolation();
            }
        }
    }
}