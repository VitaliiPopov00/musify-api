<?php

namespace App\Validator;

use App\Entity\Singer;
use App\Validator\Constraints\SingersExist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SingersExistValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof SingersExist) {
            throw new UnexpectedTypeException($constraint, SingersExist::class);
        }

        $singerIds = is_array($value) ? $value : [$value];

        $singerRepository = $this->entityManager->getRepository(Singer::class);
        
        foreach ($singerIds as $singerId) {
            if (!$singerRepository->find($singerId)) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
                return;
            }
        }
    }
} 