<?php

namespace App\Validator;

use App\Repository\UserRepository;
use App\Validator\Constraints\UniqueUserLogin;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueUserLoginValidator extends ConstraintValidator
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueUserLogin) {
            throw new UnexpectedTypeException($constraint, UniqueUserLogin::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $user = $this->userRepository->findOneBy(['login' => $value]);

        if ($user) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
