<?php

namespace App\Dto;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as AppAssert;

class UploadSongDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 60)]
        public string $song_title,

        #[Assert\NotBlank]
        #[Assert\File(
            maxSize: '20M',
            mimeTypes: ['audio/mpeg'],
            mimeTypesMessage: 'Please upload a valid MP3 audio file'
        )]
        public UploadedFile $song,

        #[Assert\NotBlank]
        #[Assert\File(
            maxSize: '20M',
            mimeTypes: ['image/jpeg', 'image/png'],
            mimeTypesMessage: 'Please upload a valid image file (JPEG or PNG)'
        )]
        public ?UploadedFile $song_img = null,

        #[Assert\NotBlank]
        #[Assert\Count(
            min: 1,
            minMessage: 'At least one genre is required',
            max: 5,
            maxMessage: 'You can specify up to 5 genres'
        )]
        #[Assert\All([
            new Assert\Length(max: 50),
            new Assert\NotBlank
        ])]
        #[AppAssert\GenresExist]
        public array $genres,
    )
    {
    }
}
