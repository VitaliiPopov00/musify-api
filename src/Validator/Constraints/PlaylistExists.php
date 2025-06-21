<?php

namespace App\Validator\Constraints;

use App\Validator\PlaylistExistsValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class PlaylistExists extends Constraint
{
    public string $message = 'Playlist is not found';

    public function validatedBy(): string
    {
        return PlaylistExistsValidator::class;
    }
} 