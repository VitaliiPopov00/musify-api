<?php

namespace App\Validator;

use App\Repository\UserPlaylistRepository;
use App\Validator\Constraints\PlaylistExists;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PlaylistExistsValidator extends ConstraintValidator
{
    public function __construct(private readonly UserPlaylistRepository $userPlaylistRepository)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof PlaylistExists) {
            throw new UnexpectedTypeException($constraint, PlaylistExists::class);
        }

        $userPlaylist = $this->userPlaylistRepository->findOneBy(['id' => $value]);

        if (!$userPlaylist) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
} 