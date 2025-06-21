<?php

namespace App\Validator\Constraints;

use App\Validator\SongNotInPlaylistValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class SongNotInPlaylist extends Constraint
{
    public string $message = 'Song is already added in playlist';

    public function __construct(
        mixed $options = null,
        ?array $groups = null,
        mixed $payload = null,
        public array $properties = []
    )
    {
        parent::__construct($options, $groups, $payload);
    }


    public function validatedBy(): string
    {
        return SongNotInPlaylistValidator::class;
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
} 