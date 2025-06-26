<?php

namespace App\Validator;

use App\Repository\GenreRepository;
use App\Validator\Constraints\UniqueGenreTitle;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueGenreTitleValidator extends ConstraintValidator
{
    public function __construct(private readonly GenreRepository $genreRepository)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueGenreTitle) {
            throw new UnexpectedTypeException($constraint, UniqueGenreTitle::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        // Проверяем уникальность без учета регистра
        $existingGenre = $this->genreRepository->findOneByTitleIgnoreCase($value);

        if ($existingGenre) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
} 