<?php

namespace App\Controller\UserPlaylist;

use App\Controller\DefaultController;
use App\Dto\AddSongToPlaylistDto;
use App\Dto\CreateUserPlaylistDto;
use App\Entity\Song;
use App\Entity\UserPlaylist;
use App\Entity\UserPlaylistSong;
use App\Entity\FavoriteSong;
use App\Entity\ReleaseSong;
use App\Repository\SongRepository;
use App\Repository\UserRepository;
use App\Repository\UserSongRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserPlaylistController extends DefaultController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    public function create(
        Request $request,
        UserRepository $userRepository,
    ): JsonResponse
    {
        /** @var CreateUserPlaylistDto $dto */
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            CreateUserPlaylistDto::class,
            'json'
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->errorValidationResponse($errors);
        }

        $user = $this->getAuthUser($request, $userRepository);
        
        $playlist = new UserPlaylist(
            title: $dto->getTitle(),
            createdBy: $user->getId()
        );

        $this->entityManager->persist($playlist);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Playlist created successfully',
            'playlist' => $playlist
        ], 200);
    }

    public function addSong(
        Request $request,
        int $id,
        SongRepository $songRepository,
        UserRepository $userRepository,
    ): JsonResponse
    {
        /** @var AddSongToPlaylistDto $dto */
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            AddSongToPlaylistDto::class,
            'json'
        );
        $dto->setPlaylistId($id);

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->errorValidationResponse($errors);
        }

        $playlist = $this->entityManager->getRepository(UserPlaylist::class)->find($id);
        $song = $songRepository->find($dto->getSongId());

        $playlistSong = new UserPlaylistSong(
            playlist: $playlist,
            song: $song
        );

        $this->entityManager->persist($playlistSong);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Song added to playlist successfully'
        ], 200);
    }

    public function getUserPlaylists(
        Request $request,
        UserRepository $userRepository,
        UserSongRepository $userSongRepository,
    ): JsonResponse
    {
        $user = $this->getAuthUser($request, $userRepository);
        
        $playlists = $this->entityManager->getRepository(UserPlaylist::class)
            ->findBy(['createdBy' => $user->getId()], ['id' => 'DESC']);

        $result = [];

        $userSongs = $userSongRepository->findByUser($user);

        $songs = [];
        foreach ($userSongs as $userSong) {
            $song = $userSong->getSong();
            $songs[] = [
                'id' => $song->getId(),
                'title' => $song->getTitle(),
                'play_count' => $song->getPlayCount(),
                'is_user_song' => true,
                'created_at' => $song->getCreatedAt()->format('Y-m-d H:i:s'),
                'uploaded_at' => $userSong->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }

        $result[] = [
            'id' => -1,
            'title' => 'Мои загруженные треки',
            'songCount' => count($songs),
            'songs' => $songs
        ];

        foreach ($playlists as $playlist) {
            $firstSong = $this->entityManager->getRepository(UserPlaylistSong::class)
                ->findOneBy(['playlist' => $playlist], ['id' => 'ASC']);
            
            $result[] = [
                'id' => $playlist->getId(),
                'title' => $playlist->getTitle(),
                'songCount' => $this->entityManager->getRepository(UserPlaylistSong::class)
                    ->count(['playlist' => $playlist]),
                'firstSongId' => $firstSong ? $firstSong->getSong()->getId() : null,
                'firstSingerId' => $firstSong ? $firstSong->getSong()->getSingers()->first()->getId() : null
            ];
        }

        return $this->json([
            'success' => true,
            'playlists' => $result,
        ]);
    }

    public function get(
        int $id,
        Request $request,
        UserRepository $userRepository,
    ): JsonResponse
    {
        $user = $this->getAuthUser($request, $userRepository);
        
        $playlist = $this->entityManager->getRepository(UserPlaylist::class)->find($id);
        if (!$playlist) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => 404,
                    'message' => 'Playlist not found',
                ],
            ], 404);
        }

        if ($playlist->getCreatedBy($userRepository)->getId() !== $user->getId()) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => 403,
                    'message' => 'Access denied',
                ],
            ], 403);
        }

        $songs = [];
        foreach ($playlist->getSongs() as $playlistSong) {
            /** @var Song $song */
            $song = $playlistSong->getSong();
            
            // Проверяем, что песня из опубликованного релиза
            $releaseSong = $this->entityManager->getRepository(ReleaseSong::class)
                ->createQueryBuilder('rs')
                ->leftJoin('rs.release', 'r')
                ->where('rs.song = :song')
                ->setParameter('song', $song)
                ->getQuery()
                ->getOneOrNullResult();
            
            // Пропускаем песни из неопубликованных релизов и пользовательские треки
            if ($releaseSong && !$releaseSong->getRelease()->getIsReleased()) {
                continue;
            }
            
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

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $playlist->getId(),
                'title' => $playlist->getTitle(),
                'createdAt' => $playlist->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $playlist->getUpdatedAt()->format('Y-m-d H:i:s'),
                'songs' => $songs,
            ],
        ]);
    }

    public function delete(
        int $playlistId,
        Request $request,
        UserRepository $userRepository,
    ): JsonResponse
    {
        $user = $this->getAuthUser($request, $userRepository);
        
        $playlist = $this->entityManager->getRepository(UserPlaylist::class)->find($playlistId);
        if (!$playlist) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => 404,
                    'message' => 'Playlist not found',
                ],
            ], 404);
        }

        if ($playlist->getCreatedBy($userRepository)->getId() !== $user->getId()) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => 403,
                    'message' => 'Access denied',
                ],
            ], 403);
        }

        $this->entityManager->remove($playlist);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'data' => [
                'message' => 'Playlist successfully deleted'
            ],
        ]);
    }

    public function removeSong(
        int $playlistId,
        int $songId,
        Request $request,
        UserRepository $userRepository,
    ): JsonResponse
    {
        $user = $this->getAuthUser($request, $userRepository);
        
        $playlist = $this->entityManager->getRepository(UserPlaylist::class)->find($playlistId);
        if (!$playlist) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => 404,
                    'message' => 'Playlist not found',
                ],
            ], 404);
        }

        if ($playlist->getCreatedBy($userRepository)->getId() !== $user->getId()) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => 403,
                    'message' => 'Access denied',
                ],
            ], 403);
        }

        $playlistSong = $this->entityManager->getRepository(UserPlaylistSong::class)
            ->findOneBy([
                'playlist' => $playlist,
                'song' => $songId
            ]);

        if (!$playlistSong) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => 404,
                    'message' => 'Song not found in playlist',
                ],
            ], 404);
        }

        $this->entityManager->remove($playlistSong);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'data' => [
                'message' => 'Song successfully removed from playlist'
            ],
        ]);
    }
} 