<?php

namespace App\Validator;

use App\Entity\Release;
use App\Validator\Constraints\ReleaseExists;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ReleaseExistsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ReleaseExists) {
            throw new UnexpectedTypeException($constraint, ReleaseExists::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $release = $this->entityManager->getRepository(Release::class)->find($value);

        if (null === $release) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ releaseId }}', $value)
                ->addViolation();
        }
    }
} 