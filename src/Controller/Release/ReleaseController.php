<?php

namespace App\Controller\Release;

use App\Controller\DefaultController;
use App\Dto\ReleaseCreateDto;
use App\Entity\Song;
use App\Service\ReleaseService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use getID3;
use App\Entity\FavoriteSong;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Release;
use App\Entity\ReleaseSong;
use App\Entity\ReleaseSinger;
use App\Entity\ListeningHistory;
use App\Entity\UserPlaylistSong;

class ReleaseController extends DefaultController
{
    public function __construct(
        private readonly ReleaseService $releaseService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function create(
        Request $request,
        UserRepository $userRepository,
    ): JsonResponse
    {
        $user = $this->getAuthUser($request, $userRepository);
        
        /** @var ReleaseCreateDto $dto */
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            ReleaseCreateDto::class,
            'json'
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->errorValidationResponse($errors);
        }

        $release = $this->releaseService->create($dto, $user->getId());

        return $this->json([
            'success' => true,
            'data' => [
                'message' => 'Release created successfully',
                'release_id' => $release->getId(),
            ],
        ]);
    }

    public function get(
        int $id,
        Request $request,
        UserRepository $userRepository,
    ): JsonResponse
    {
        $release = $this->releaseService->get($id);
        if (!$release) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => 404,
                    'message' => 'Release not found',
                ],
            ], 404);
        }

        $user = $this->getAuthUser($request, $userRepository);

        if (!$release->getIsReleased() && $release->getCreatedBy() !== $user->getId()) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => 403,
                    'message' => 'Release is not published yet',
                ],
            ], 403);
        }

        $songs = [];
        foreach ($release->getReleaseSongs() as $releaseSong) {
            /** @var Song $song */
            $song = $releaseSong->getSong();
            
            // Пропускаем пользовательские треки
            if ($song->isUserSong()) {
                continue;
            }
            
            $songSingers = [];
            foreach ($song->getSingers() as $singer) {
                $songSingers[] = [
                    'id' => $singer->getId(),
                    'name' => $singer->getName(),
                ];
            }

            $genres = [];
            foreach ($song->getGenres() as $genre) {
                $genres[] = [
                    'id' => $genre->getId(),
                    'title' => $genre->getTitle(),
                ];
            }

            $filePath = sprintf('%d/%d/song.mp3', $song->getSingers()->first()->getId(), $song->getId());
            $absolutePath = $this->getParameter('upload_directory') . '/' . $filePath;
            $duration = 0;
            
            if (file_exists($absolutePath)) {
                $getID3 = new \getID3();
                $fileInfo = $getID3->analyze($absolutePath);
                $duration = isset($fileInfo['playtime_seconds']) ? round($fileInfo['playtime_seconds']) : 0;
            }

            $isFavorite = $this->entityManager->getRepository(FavoriteSong::class)
                ->findOneBy([
                    'user' => $user,
                    'song' => $song,
                ]) !== null;

            $songs[] = [
                'id' => $song->getId(),
                'title' => $song->getTitle(),
                'genres' => $genres,
                'singers' => $songSingers,
                'isFavorite' => $isFavorite,
                'duration' => $duration,
                'playCount' => $song->getPlayCount(),
            ];
        }

        $singers = [];
        foreach ($release->getReleaseSingers() as $releaseSinger) {
            $singer = $releaseSinger->getSinger();
            $singers[] = [
                'id' => $singer->getId(),
                'name' => $singer->getName(),
            ];
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $release->getId(),
                'title' => $release->getTitle(),
                'date' => $release->getDate()->format('Y-m-d'),
                'time' => $release->getTime()->format('H:i:s'),
                'is_released' => (bool) $release->getIsReleased(),
                'songs' => $songs,
                'singers' => $singers,
            ],
        ]);
    }

    public function getBySinger(
        int $singerId,
        Request $request,
        UserRepository $userRepository,
    ): JsonResponse
    {
        $user = $this->getAuthUser($request, $userRepository);
        
        $releases = $this->releaseService->getBySinger($singerId);
        
        $result = [];
        foreach ($releases as $release) {
            $firstSong = $release->getReleaseSongs()->first();
            $result[] = [
                'id' => $release->getId(),
                'name' => $release->getTitle(),
                'date' => $release->getDate()->format('Y-m-d'),
                'first_song_id' => $firstSong ? $firstSong->getSong()->getId() : null,
            ];
        }

        return $this->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function getBySingerFuture(
        int $singerId,
        Request $request,
        UserRepository $userRepository,
    ): JsonResponse
    {
        $user = $this->getAuthUser($request, $userRepository);

        $releases = $this->releaseService->getBySingerFuture($singerId);

        $result = [];
        foreach ($releases as $release) {
            $firstSong = $release->getReleaseSongs()->first();
            $result[] = [
                'id' => $release->getId(),
                'name' => $release->getTitle(),
                'date' => $release->getDate()->format('Y-m-d'),
                'time' => $release->getTime()->format('H:i:s'),
                'first_song_id' => $firstSong ? $firstSong->getSong()->getId() : null,
            ];
        }

        return $this->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function deleteRelease(
        int $releaseId,
        Request $request,
        UserRepository $userRepository,
    ): JsonResponse
    {
        $user = $this->getAuthUser($request, $userRepository);
        
        $release = $this->releaseService->get($releaseId);
        if (!$release) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => 404,
                    'message' => 'Release not found',
                ],
            ], 404);
        }

        // Проверяем, что пользователь является создателем релиза
        if ($release->getCreatedBy() !== $user->getId()) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => 403,
                    'message' => 'You can only delete your own releases',
                ],
            ], 403);
        }

        try {
            // Получаем все песни релиза перед удалением связей
            $releaseSongs = $release->getReleaseSongs();
            $songsToDelete = [];
            
            foreach ($releaseSongs as $releaseSong) {
                $song = $releaseSong->getSong();
                $songsToDelete[] = $song;
            }

            // Удаляем связанные записи ReleaseSong
            $this->entityManager->getRepository(ReleaseSong::class)->createQueryBuilder('rs')
                ->delete()
                ->where('rs.release = :release')
                ->setParameter('release', $release)
                ->getQuery()
                ->execute();

            // Удаляем связанные записи ReleaseSinger
            $this->entityManager->getRepository(ReleaseSinger::class)->createQueryBuilder('rs')
                ->delete()
                ->where('rs.release = :release')
                ->setParameter('release', $release)
                ->getQuery()
                ->execute();

            // Удаляем все песни релиза
            foreach ($songsToDelete as $song) {
                // Удаляем файлы песни
                $firstSinger = $song->getSingers()->first();
                if ($firstSinger) {
                    $songFilePath = sprintf('%d/%d/song.mp3', $firstSinger->getId(), $song->getId());
                    $songImagePath = sprintf('%d/%d/photo.png', $firstSinger->getId(), $song->getId());
                    
                    $absoluteSongPath = $this->getParameter('upload_directory') . '/' . $songFilePath;
                    $absoluteImagePath = $this->getParameter('upload_directory') . '/' . $songImagePath;
                    
                    if (file_exists($absoluteSongPath)) {
                        unlink($absoluteSongPath);
                    }
                    
                    if (file_exists($absoluteImagePath)) {
                        unlink($absoluteImagePath);
                    }
                }

                // Удаляем связанные записи песни
                $this->entityManager->getRepository(FavoriteSong::class)->createQueryBuilder('fs')
                    ->delete()
                    ->where('fs.song = :song')
                    ->setParameter('song', $song)
                    ->getQuery()
                    ->execute();

                $this->entityManager->getRepository(ListeningHistory::class)->createQueryBuilder('lh')
                    ->delete()
                    ->where('lh.song = :song')
                    ->setParameter('song', $song)
                    ->getQuery()
                    ->execute();

                // Удаляем песню из пользовательских плейлистов
                $this->entityManager->getRepository(UserPlaylistSong::class)->createQueryBuilder('ups')
                    ->delete()
                    ->where('ups.song = :song')
                    ->setParameter('song', $song)
                    ->getQuery()
                    ->execute();

                // Удаляем саму песню
                $this->entityManager->remove($song);
            }

            // Удаляем сам релиз
            $this->entityManager->remove($release);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'data' => [
                    'message' => 'Release and all its songs deleted successfully',
                ],
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => 500,
                    'message' => 'Error deleting release: ' . $e->getMessage(),
                ],
            ], 500);
        }
    }
} 
