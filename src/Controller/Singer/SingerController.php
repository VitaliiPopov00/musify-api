<?php

namespace App\Controller\Singer;

use App\Controller\DefaultController;
use App\Dto\SingerCreateDto;
use App\Entity\Singer;
use App\Repository\GenreRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Repository\SingerRepository;

class SingerController extends DefaultController
{
    public function create(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        GenreRepository $genreRepository,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
    ): JsonResponse
    {
        /** @var SingerCreateDto $singerDto */
        $singerCreateDto = $serializer->deserialize(
            $request->getContent(),
            SingerCreateDto::class,
            'json'
        );

        $errors = $validator->validate($singerCreateDto);

        if (count($errors) > 0) {
            return $this->errorValidationResponse($errors);
        }

        $user = $this->getAuthUser($request, $userRepository);

        if ($user->getSinger()) {
            return $this->json([
                'success' => false,
                'data' => [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'You already have a singer profile',
                ],
            ]);
        }

        $singer = (new Singer())
            ->setName($singerCreateDto->name)
            ->setDescription($singerCreateDto->description)
            ->setUser($user);

        foreach ($singerCreateDto->genres as $genreTitle) {
            $genre = $genreRepository->findOneBy(['title' => $genreTitle]);
            $singer->addGenre($genre);
        }

        $entityManager->persist($singer);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'data' => [
                'message' => 'Singer profile created successfully',
                'singerId' => $singer->getId(),
            ],
        ]);
    }

    public function getPlaylist(
        int $singerId,
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 100);
        $offset = (int) $request->query->get('offset', 0);

        $singer = $entityManager->getRepository(Singer::class)->find($singerId);
        if (!$singer) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Singer not found',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        $songs = $singer->getSongs()->slice($offset, $limit);
        $songsData = [];
        $total = $singer->getSongs()->count();

        foreach ($songs as $song) {
            $singers = [];
            foreach ($song->getSingers() as $songSinger) {
                $singers[] = [
                    'id' => $songSinger->getId(),
                    'name' => $songSinger->getName(),
                ];
            }

            $genres = [];
            foreach ($song->getGenres() as $genre) {
                $genres[] = [
                    'id' => $genre->getId(),
                    'title' => $genre->getTitle(),
                ];
            }

            $songsData[] = [
                'id' => $song->getId(),
                'title' => $song->getTitle(),
                'createdAt' => $song->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $song->getUpdatedAt()->format('Y-m-d H:i:s'),
                'singers' => $singers,
                'genres' => $genres,
            ];
        }

        return $this->json([
            'success' => true,
            'data' => [
                'items' => $songsData,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'hasMore' => ($offset + $limit) < $total,
            ],
        ]);
    }
}
