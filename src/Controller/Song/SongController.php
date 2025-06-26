<?php

namespace App\Controller\Song;

use App\Controller\DefaultController;
use App\Dto\UploadSongDto;
use App\Entity\CustomGenre;
use App\Entity\FavoriteSong;
use App\Entity\ListeningHistory;
use App\Entity\Song;
use App\Entity\Release;
use App\Entity\ReleaseSong;
use App\Entity\Singer;
use App\Entity\ReleaseSinger;
use App\Enum\CustomGenreEnum;
use App\Repository\FavoriteSongRepository;
use App\Repository\GenreRepository;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use getID3;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\UserPlaylistSong;

class SongController extends DefaultController
{
    public function streamSong(
        int $artistId,
        int $songId,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
    ): Response
    {
        $filePath = sprintf('%d/%d/song.mp3', $artistId, $songId);
        $absolutePath = $this->getParameter('upload_directory') . '/' . $filePath;

        if (!file_exists($absolutePath)) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Song file not found',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        $song = $entityManager->getRepository(Song::class)->find($songId);
        if ($song) {
            $song->incrementPlayCount();
            $entityManager->flush();
        }

        $response = new BinaryFileResponse($absolutePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            sprintf('%d.mp3', $songId)
        );

        return $response;
    }

    public function upload(
        Request $request,
        ValidatorInterface $validator,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        FileUploader $fileUploader,
        GenreRepository $genreRepository,
    ): JsonResponse
    {
        $user = $this->getAuthUser(
            $request,
            $userRepository
        );

        if (!$user->getSinger()) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'Only singers can upload songs',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        $songFile = $request->files->get('song');
        $songImgFile = $request->files->get('song_img');
        $songTitle = $request->request->get('song_title');
        $songCustomGenre = $request->request->get('custom_genre');
        $releaseId = $request->request->get('release_id');

        if ($songFile === null || !($songFile instanceof UploadedFile)) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'errors' => [
                        'song' => ['Required song file'],
                    ],
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($songImgFile === null || !$songImgFile instanceof UploadedFile) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'errors' => [
                        'song_img' => ['Required song image'],
                    ],
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        $genreIds = explode(',', $request->request->get('genres'));
        $fileUploadDto = new UploadSongDto(
            $songTitle,
            $songFile,
            $songImgFile,
            $genreIds,
            $songCustomGenre,
            $releaseId
        );

        $errors = $validator->validate($fileUploadDto);
        if (count($errors) > 0) {
            return $this->errorValidationResponse($errors);
        }

        $song = new Song();
        $song->setTitle($songTitle);
        $song->addSinger($user->getSinger());

        foreach ($genreIds as $genreId) {
            $genre = $genreRepository->findOneBy(['id' => $genreId]);
            $song->addGenre($genre);
        }

        $entityManager->persist($song);
        $entityManager->flush();

        try {
            $fileUploader->uploadSong(
                $songFile,
                $user->getSinger()->getId(),
                $song->getId()
            );

            if ($songImgFile) {
                $fileUploader->uploadSongImage(
                    $songImgFile,
                    $user->getSinger()->getId(),
                    $song->getId()
                );
            }
        } catch (\Exception $e) {
            $entityManager->remove($song);
            $entityManager->flush();

            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => 'Error uploading file',
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!empty($songCustomGenre)) {
            $customGenre = new CustomGenre(
                title: $songCustomGenre,
                entityType: CustomGenreEnum::song->value,
                entityId: $song->getId(),
                createdBy: $user
            );

            $entityManager->persist($customGenre);
            $entityManager->flush();
        }

        if ($releaseId) {
            $release = $entityManager->getRepository(Release::class)->find($releaseId);
            $releaseSong = new ReleaseSong();
            $releaseSong->setRelease($release);
            $releaseSong->setSong($song);
            $releaseSong->setCreatedBy($user);

            $entityManager->persist($releaseSong);
            $entityManager->flush();
        }

        return $this->json([
            'success' => true,
            'data' => [
                'message' => 'Song uploaded successfully',
                'songId' => $song->getId(),
            ],
        ]);
    }

    public function getLatestSongs(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $user = $this->getAuthUser($request, $userRepository);
        
        $songs = $entityManager->getRepository(Song::class)
            ->createQueryBuilder('s')
            ->leftJoin('s.singers', 'singers')
            ->leftJoin('s.genres', 'genres')
            ->leftJoin('App\Entity\ReleaseSong', 'rs', 'WITH', 'rs.song = s')
            ->leftJoin('rs.release', 'r')
            ->select('s', 'singers', 'genres')
            ->where('r.isReleased = 1 OR r.id IS NULL')
            ->andWhere('s.isUserSong = 0')
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(10)
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

    public function addToFavorites(
        int $songId,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        FavoriteSongRepository $favoriteSongRepository
    ): JsonResponse {
        $user = $this->getAuthUser($request, $userRepository);
        $song = $entityManager->getRepository(Song::class)->find($songId);

        if (!$song) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Song not found',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        $existingFavorite = $favoriteSongRepository->findByUserAndSong($user, $song);
        if ($existingFavorite) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'Song is already in favorites',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        $favoriteSong = new FavoriteSong();
        $favoriteSong->setUser($user);
        $favoriteSong->setSong($song);

        $entityManager->persist($favoriteSong);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'data' => [
                'message' => 'Song added to favorites',
            ],
        ]);
    }

    public function removeFromFavorites(
        int $songId,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        FavoriteSongRepository $favoriteSongRepository
    ): JsonResponse {
        $user = $this->getAuthUser($request, $userRepository);
        $song = $entityManager->getRepository(Song::class)->find($songId);

        if (!$song) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Song not found',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        $favoriteSong = $favoriteSongRepository->findByUserAndSong($user, $song);
        if (!$favoriteSong) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'Song is not in favorites',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->remove($favoriteSong);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'data' => [
                'message' => 'Song removed from favorites',
            ],
        ]);
    }

    public function getUserFavoriteSongs(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $user = $this->getAuthUser($request, $userRepository);

        $favoriteSongs = $entityManager->getRepository(FavoriteSong::class)
            ->createQueryBuilder('fs')
            ->leftJoin('fs.song', 's')
            ->leftJoin('s.singers', 'singers')
            ->leftJoin('s.genres', 'genres')
            ->leftJoin('App\Entity\ReleaseSong', 'rs', 'WITH', 'rs.song = s')
            ->leftJoin('rs.release', 'r')
            ->where('fs.user = :user')
            ->andWhere('r.isReleased = 1 OR r.id IS NULL')
            ->andWhere('s.isUserSong = 0')
            ->setParameter('user', $user)
            ->orderBy('fs.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $songsData = [];
        
        foreach ($favoriteSongs as $favoriteSong) {
            $song = $favoriteSong->getSong();
            
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

            $filePath = sprintf('%d/%d/song.mp3', $song->getSingers()->first()->getId(), $song->getId());
            $absolutePath = $this->getParameter('upload_directory') . '/' . $filePath;
            $duration = 0;
            
            if (file_exists($absolutePath)) {
                $getID3 = new getID3();
                $fileInfo = $getID3->analyze($absolutePath);
                $duration = isset($fileInfo['playtime_seconds']) ? round($fileInfo['playtime_seconds']) : 0;
            }

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
                'isFavorite' => true,
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

    public function incrementPlayCount(
        int $songId,
        EntityManagerInterface $entityManager,
        Request $request,
        UserRepository $userRepository
    ): JsonResponse {
        $song = $entityManager->getRepository(Song::class)->find($songId);
        
        if (!$song) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Song not found',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getAuthUser($request, $userRepository);

        $listeningHistory = $entityManager->getRepository(ListeningHistory::class)->findOneBy([
            'user' => $user,
            'song' => $song
        ]);

        if ($listeningHistory) {
            $listeningHistory->setListenedAt(new \DateTimeImmutable());
        } else {
            $listeningHistory = new ListeningHistory();
            $listeningHistory->setUser($user);
            $listeningHistory->setSong($song);
        }
        
        $entityManager->persist($listeningHistory);

        $song->incrementPlayCount();
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'data' => [
                'message' => 'Play count incremented successfully',
                'playCount' => $song->getPlayCount(),
            ],
        ]);
    }

    public function deleteSong(
        int $songId,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        FileUploader $fileUploader
    ): JsonResponse {
        $user = $this->getAuthUser($request, $userRepository);
        
        // Проверяем, что пользователь является исполнителем
        if (!$user->getSinger()) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Only singers can delete songs',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        $song = $entityManager->getRepository(Song::class)->find($songId);
        if (!$song) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Song not found',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        // Проверяем, что песня принадлежит исполнителю
        $songBelongsToSinger = false;
        foreach ($song->getSingers() as $singer) {
            if ($singer->getId() === $user->getSinger()->getId()) {
                $songBelongsToSinger = true;
                break;
            }
        }

        if (!$songBelongsToSinger) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => 'You can only delete your own songs',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        try {
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

            // Удаляем связанные записи
            $entityManager->getRepository(FavoriteSong::class)->createQueryBuilder('fs')
                ->delete()
                ->where('fs.song = :song')
                ->setParameter('song', $song)
                ->getQuery()
                ->execute();

            $entityManager->getRepository(ListeningHistory::class)->createQueryBuilder('lh')
                ->delete()
                ->where('lh.song = :song')
                ->setParameter('song', $song)
                ->getQuery()
                ->execute();

            $entityManager->getRepository(ReleaseSong::class)->createQueryBuilder('rs')
                ->delete()
                ->where('rs.song = :song')
                ->setParameter('song', $song)
                ->getQuery()
                ->execute();

            // Удаляем песню из пользовательских плейлистов
            $entityManager->getRepository(UserPlaylistSong::class)->createQueryBuilder('ups')
                ->delete()
                ->where('ups.song = :song')
                ->setParameter('song', $song)
                ->getQuery()
                ->execute();

            // Проверяем, не остались ли пустые релизы после удаления песни
            $emptyReleases = $entityManager->getRepository(Release::class)
                ->createQueryBuilder('r')
                ->leftJoin('r.releaseSongs', 'rs')
                ->where('rs.id IS NULL')
                ->getQuery()
                ->getResult();

            foreach ($emptyReleases as $emptyRelease) {
                // Удаляем связи с исполнителями
                $entityManager->getRepository(ReleaseSinger::class)->createQueryBuilder('rs')
                    ->delete()
                    ->where('rs.release = :release')
                    ->setParameter('release', $emptyRelease)
                    ->getQuery()
                    ->execute();

                // Удаляем сам релиз
                $entityManager->remove($emptyRelease);
            }

            // Удаляем саму песню
            $entityManager->remove($song);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'data' => [
                    'message' => 'Song deleted successfully',
                ],
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => 'Error deleting song: ' . $e->getMessage(),
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

