<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

readonly class FileUploader
{
    public function __construct(
        private readonly string $uploadDirectory,
        private readonly SluggerInterface $slugger
    ) {
    }

    public function upload(UploadedFile $file, string $directory): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $uploadPath = $this->uploadDirectory . '/' . $directory;
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $file->move($uploadPath, $newFilename);

        return $directory . '/' . $newFilename;
    }

    public function uploadSong(UploadedFile $file, int $singerId, int $songId): string
    {
        $uploadPath = $this->uploadDirectory . '/' . $singerId . '/' . $songId;
        
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $filename = sprintf('song.mp3', $songId);
        $file->move($uploadPath, $filename);

        return $uploadPath . '/' . $filename;
    }

    public function uploadSongImage(UploadedFile $file, int $singerId, int $songId): string
    {
        $uploadPath = $this->uploadDirectory . '/' . $singerId . '/' . $songId;
        
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $filename = 'photo.png';
        $file->move($uploadPath, $filename);

        return $uploadPath . '/' . $filename;
    }
} 