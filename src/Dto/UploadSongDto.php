<?php

namespace App\Dto;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as AppAssert;
use App\Validator\Constraints\ReleaseExists;

class UploadSongDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Song title is required')]
        #[Assert\Length(max: 60, maxMessage: 'Song title is too long')]
        public string $song_title,

        #[Assert\NotBlank(message: 'Song file is required')]
        #[Assert\Type(type: UploadedFile::class, message: 'Song file must be a valid file')]
        #[Assert\File(
            maxSize: '20M',
            maxSizeMessage: 'Song file is too large (max 20MB)',
            mimeTypes: ['audio/mpeg'],
            mimeTypesMessage: 'Please upload a valid MP3 audio file'
        )]
        public UploadedFile $song,

        #[Assert\Type(type: UploadedFile::class, message: 'Song image must be a valid file')]
        #[Assert\File(
            maxSize: '5M',
            maxSizeMessage: 'Song image is too large (max 5MB)',
            mimeTypes: ['image/jpeg', 'image/png'],
            mimeTypesMessage: 'Please upload a valid image file (JPEG or PNG)'
        )]
        public ?UploadedFile $song_img = null,

        #[Assert\NotBlank(message: 'At least one genre is required')]
        #[Assert\Count(
            min: 1,
            minMessage: 'At least one genre is required',
            max: 5,
            maxMessage: 'You can specify up to 5 genres'
        )]
        #[Assert\All([
            new Assert\Length(max: 5, maxMessage: 'Genre title is too long'),
            new Assert\NotBlank(message: 'Genre title cannot be empty')
        ])]
        #[AppAssert\GenresExist]
        public array $genres,

        #[Assert\Length(max: 30)]
        #[Assert\Type('string')]
        public ?string $custom_genre = null,

        #[Assert\NotBlank(message: 'Release id cannot be empty')]
        #[ReleaseExists]
        private readonly ?int $release_id = null,
    )
    {
    }

    public function getReleaseId(): ?int
    {
        return $this->release_id;
    }
}
