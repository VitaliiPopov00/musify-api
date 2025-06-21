<?php

namespace App\Controller\Genre;

use App\Controller\DefaultController;
use App\Entity\Genre;
use App\Repository\GenreRepository;
use Symfony\Component\HttpFoundation\Response;

class GenreController extends DefaultController
{
    public function list(
        GenreRepository $genreRepository
    ): Response
    {
        $genres = $genreRepository->findBy([], ['id' => 'ASC'], 100);
        
        $data = array_map(function (Genre $genre) {
            return [
                'id' => $genre->getId(),
                'title' => $genre->getTitle(),
                'createdAt' => $genre->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $genre->getUpdatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $genres);

        return $this->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
