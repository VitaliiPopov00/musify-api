<?php

namespace App\Validator;

use App\Dto\AddSongToPlaylistDto;
use App\Repository\UserPlaylistSongRepository;
use App\Validator\Constraints\SongNotInPlaylist;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SongNotInPlaylistValidator extends ConstraintValidator
{
    public function __construct(private readonly UserPlaylistSongRepository $userPlaylistSongRepository)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof SongNotInPlaylist) {
            throw new UnexpectedTypeException($constraint, SongNotInPlaylist::class);
        }

        /** @var $value AddSongToPlaylistDto */
        $songIsExistsInPlaylist = (bool) $this->userPlaylistSongRepository->findOneByPlaylistAndSong(
            $value->getPlaylistId(),
            $value->getSongId(),
        );

        if ($songIsExistsInPlaylist) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
} 