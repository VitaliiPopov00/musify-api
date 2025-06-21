<?php

namespace App\Controller\Singer;

use App\Controller\DefaultController;
use App\Dto\SingerCreateDto;
use App\Entity\Singer;
use App\Enum\CustomGenreEnum;
use App\Repository\GenreRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Repository\SingerRepository;
use App\Entity\CustomGenre;
use App\Entity\Song;
use App\Entity\FavoriteSong;
use App\Entity\ReleaseSong;
use App\Entity\Subscribe;
use getID3;

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
        $user = $this->getAuthUser($request, $userRepository);

        if ($user->getSinger()) {
            return $this->json([
                'success' => false,
                'data' => [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'You already have a singer profile',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

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

        $singer = (new Singer())
            ->setName($singerCreateDto->name)
            ->setDescription($singerCreateDto->description)
            ->setUser($user);

        foreach ($singerCreateDto->genres as $genreId) {
            $genre = $genreRepository->findOneBy(['id' => $genreId]);
            $singer->addGenre($genre);
        }

        $entityManager->persist($singer);
        $entityManager->flush();

        if ($singerCreateDto->customGenre) {
            $customGenre = new CustomGenre(
                title: $singerCreateDto->customGenre,
                entityType: CustomGenreEnum::singer->value,
                entityId: $singer->getId(),
                createdBy: $user
            );

            $entityManager->persist($customGenre);
            $entityManager->flush();
        }

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

    public function getSongs(
        int $singerId,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $user = $this->getAuthUser($request, $userRepository);
        
        $singer = $entityManager->getRepository(Singer::class)->find($singerId);
        if (!$singer) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Artist not found',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        $limit = (int) $request->query->get('limit', 100);

        $qb = $entityManager->getRepository(Song::class)
            ->createQueryBuilder('s')
            ->select('DISTINCT s.id, s.playCount')
            ->leftJoin('s.singers', 'singers')
            ->where('singers.id = :singerId')
            ->setParameter('singerId', $singerId)
            ->orderBy('s.playCount', 'DESC')
            ->setMaxResults($limit);

        $songIds = array_column($qb->getQuery()->getResult(), 'id');

        $songs = $entityManager->getRepository(Song::class)
            ->createQueryBuilder('s')
            ->leftJoin('s.singers', 'singers')
            ->leftJoin('s.genres', 'genres')
            ->where('s.id IN (:songIds)')
            ->setParameter('songIds', $songIds)
            ->select('s', 'singers', 'genres')
            ->orderBy('s.playCount', 'DESC')
            ->getQuery()
            ->getResult();

        $songsData = [];
        
        foreach ($songs as $song) {
            $singersData = [];
            foreach ($song->getSingers() as $singer) {
                $singersData[] = [
                    'id' => $singer->getId(),
                    'name' => $singer->getName(),
                ];
            }

            $genresData = [];
            foreach ($song->getGenres() as $genre) {
                $genresData[] = [
                    'id' => $genre->getId(),
                    'title' => $genre->getTitle(),
                ];
            }

            $filePath = sprintf('%d/%d/song.mp3', $singerId, $song->getId());
            $absolutePath = $this->getParameter('upload_directory') . '/' . $filePath;
            $duration = 0;
            
            if (file_exists($absolutePath)) {
                $getID3 = new getID3();
                $fileInfo = $getID3->analyze($absolutePath);
                $duration = isset($fileInfo['playtime_seconds']) ? round($fileInfo['playtime_seconds']) : 0;
            }

            $isFavorite = $entityManager->getRepository(FavoriteSong::class)
                ->findOneBy([
                    'user' => $user,
                    'song' => $song,
                ]) !== null;

            // Получаем информацию о релизе
            $releaseData = null;
            $releaseSong = $entityManager->getRepository(ReleaseSong::class)
                ->createQueryBuilder('rs')
                ->leftJoin('rs.release', 'r')
                ->where('rs.song = :song')
                ->setParameter('song', $song)
                ->select('r.id', 'r.title')
                ->getQuery()
                ->getOneOrNullResult();
            
            if ($releaseSong) {
                $releaseData = [
                    'id' => $releaseSong['id'],
                    'title' => $releaseSong['title'],
                ];
            }

            $songsData[] = [
                'id' => $song->getId(),
                'title' => $song->getTitle(),
                'playCount' => $song->getPlayCount(),
                'createdAt' => $song->getCreatedAt()->format('Y-m-d H:i:s'),
                'singers' => $singersData,
                'genres' => $genresData,
                'isFavorite' => $isFavorite,
                'duration' => $duration,
                'release' => $releaseData,
            ];
        }

        return $this->json([
            'success' => true,
            'data' => [
                'songs' => $songsData,
            ],
        ]);
    }

    public function getSingerInfo(
        int $singerId,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $user = $this->getAuthUser($request, $userRepository);
        
        $singer = $entityManager->getRepository(Singer::class)->find($singerId);
        if (!$singer) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Певца не найдено',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        $totalPlayCount = $entityManager->getRepository(Song::class)
            ->createQueryBuilder('s')
            ->select('SUM(s.playCount) as total')
            ->innerJoin('s.singers', 'singers')
            ->where('singers.id = :singerId')
            ->setParameter('singerId', $singerId)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $subscribe = $entityManager->getRepository(Subscribe::class)->findOneBy([
            'singerId' => $singer->getId(),
            'userId' => $user->getId()
        ]);
        $isSubscribe = $subscribe !== null;

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $singer->getId(),
                'name' => $singer->getName(),
                'totalPlayCount' => (int) $totalPlayCount,
                'isSubscribe' => $isSubscribe,
                'singerIsUser' => $singer->getUser()->getId() === $user->getId(),
            ],
        ]);
    }
}
