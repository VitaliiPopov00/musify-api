<?php

namespace App\Validator;

use App\Repository\SingerRepository;
use App\Validator\Constraints\UniqueSingerName;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueSingerNameValidator extends ConstraintValidator
{
    public function __construct(private readonly SingerRepository $userRepository)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueSingerName) {
            throw new UnexpectedTypeException($constraint, UniqueSingerName::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $user = $this->userRepository->findOneBy(['name' => $value]);

        if ($user) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
