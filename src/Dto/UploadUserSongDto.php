<?php

namespace App\Dto;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class UploadUserSongDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Song file is required')]
        #[Assert\Type(type: UploadedFile::class, message: 'Song file must be a valid file')]
        #[Assert\File(
            maxSize: '20M',
            maxSizeMessage: 'Song file is too large (max 20MB)',
            mimeTypes: ['audio/mpeg'],
            mimeTypesMessage: 'Please upload a valid MP3 audio file'
        )]
        public UploadedFile $song,
    )
    {
    }
} 