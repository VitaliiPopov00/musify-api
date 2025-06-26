<?php

namespace App\Controller\UserSong;

use App\Controller\DefaultController;
use App\Dto\UploadUserSongDto;
use App\Entity\Song;
use App\Entity\UserSong;
use App\Repository\UserRepository;
use App\Repository\UserSongRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserSongController extends DefaultController
{
    public function uploadSongs(
        Request $request,
        ValidatorInterface $validator,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        FileUploader $fileUploader,
        UserSongRepository $userSongRepository
    ): JsonResponse
    {
        $user = $this->getAuthUser($request, $userRepository);
        
        $uploadedFiles = $request->files->all();
        
        if (empty($uploadedFiles)) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'No files uploaded',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        $uploadedSongs = [];
        $errors = [];

        foreach ($uploadedFiles as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            // Проверяем, что файл является MP3 и не больше 20MB
            if ($file->getMimeType() !== 'audio/mpeg') {
                $errors[] = "File {$file->getClientOriginalName()} is not a valid MP3 file";
                continue;
            }

            if ($file->getSize() > 20 * 1024 * 1024) { // 20MB в байтах
                $errors[] = "File {$file->getClientOriginalName()} is too large (max 20MB)";
                continue;
            }

            $uploadDto = new UploadUserSongDto($file);
            $validationErrors = $validator->validate($uploadDto);
            
            if (count($validationErrors) > 0) {
                foreach ($validationErrors as $error) {
                    $errors[] = "File {$file->getClientOriginalName()}: " . $error->getMessage();
                }
                continue;
            }

            // Извлекаем название трека из имени файла
            $songTitle = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            
            // Проверяем, не загружал ли пользователь уже этот трек
            // $existingSong = $entityManager->getRepository(Song::class)->findOneBy(['title' => $songTitle]);
            // if ($existingSong) {
            //     $existingUserSong = $userSongRepository->findOneByUserAndSong($user, $existingSong->getId());
            //     if ($existingUserSong) {
            //         $errors[] = "Song '{$songTitle}' already uploaded by you";
            //         continue;
            //     }
            // }

            try {
                // Создаем новую песню
                $song = new Song();
                $song->setTitle($songTitle);
                $song->setIsUserSong(true);
                
                $entityManager->persist($song);
                $entityManager->flush();

                // Создаем связь пользователя с песней
                $userSong = new UserSong();
                $userSong->setUser($user);
                $userSong->setSong($song);
                
                $entityManager->persist($userSong);

                // Загружаем файл
                $uploadPath = $fileUploader->uploadUserSong($file, $user->getId(), $song->getId());
                
                $entityManager->flush();

                // Получаем длительность файла
                $filePath = sprintf('user/%d/%d/song.mp3', $user->getId(), $song->getId());
                $absolutePath = $this->getParameter('upload_directory') . '/' . $filePath;
                $duration = 0;
                
                if (file_exists($absolutePath)) {
                    $getID3 = new \getID3();
                    $fileInfo = $getID3->analyze($absolutePath);
                    $duration = isset($fileInfo['playtime_seconds']) ? round($fileInfo['playtime_seconds']) : 0;
                }

                $uploadedSongs[] = [
                    'id' => $song->getId(),
                    'title' => $song->getTitle(),
                    'duration' => $duration,
                    'uploaded_at' => $userSong->getCreatedAt()->format('Y-m-d H:i:s')
                ];

            } catch (\Exception $e) {
                $errors[] = "Error uploading file {$file->getClientOriginalName()}: " . $e->getMessage();
                
                // Откатываем изменения для этого файла
                if (isset($song) && $song->getId()) {
                    $entityManager->remove($song);
                }
                if (isset($userSong)) {
                    $entityManager->remove($userSong);
                }
                $entityManager->flush();
            }
        }

        $response = [
            'success' => true,
            'uploaded_songs' => $uploadedSongs
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return $this->json($response, Response::HTTP_OK);
    }

    public function getUserSongs(
        Request $request,
        UserRepository $userRepository,
        UserSongRepository $userSongRepository
    ): JsonResponse
    {
        $user = $this->getAuthUser($request, $userRepository);
        
        $userSongs = $userSongRepository->findByUser($user);
        
        $songs = [];
        foreach ($userSongs as $userSong) {
            $song = $userSong->getSong();
            
            // Получаем длительность файла
            $filePath = sprintf('user/%d/%d/song.mp3', $user->getId(), $song->getId());
            $absolutePath = $this->getParameter('upload_directory') . '/' . $filePath;
            $duration = 0;
            
            if (file_exists($absolutePath)) {
                $getID3 = new \getID3();
                $fileInfo = $getID3->analyze($absolutePath);
                $duration = isset($fileInfo['playtime_seconds']) ? round($fileInfo['playtime_seconds']) : 0;
            }
            
            $songs[] = [
                'id' => $song->getId(),
                'title' => $song->getTitle(),
                'play_count' => $song->getPlayCount(),
                'is_user_song' => true,
                'duration' => $duration,
                'created_at' => $song->getCreatedAt()->format('Y-m-d H:i:s'),
                'uploaded_at' => $userSong->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => -1,
                'title' => 'Мои загруженные треки',
                'songs' => $songs,
                'total' => count($songs),
            ],
        ]);
    }

    public function deleteUserSong(
        int $songId,
        Request $request,
        UserRepository $userRepository,
        UserSongRepository $userSongRepository,
        EntityManagerInterface $entityManager,
        FileUploader $fileUploader
    ): JsonResponse
    {
        $user = $this->getAuthUser($request, $userRepository);
        
        $userSong = $userSongRepository->findOneByUserAndSong($user, $songId);
        
        if (!$userSong) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Song not found or not owned by you',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        $song = $userSong->getSong();
        
        try {
            // Удаляем файл
            $filePath = $fileUploader->getUploadDirectory() . '/user/' . $user->getId() . '/' . $songId . '/song.mp3';
            if (file_exists($filePath)) {
                unlink($filePath);
                // Удаляем папку, если она пустая
                $dir = dirname($filePath);
                if (is_dir($dir) && count(scandir($dir)) <= 2) { // . и ..
                    rmdir($dir);
                }
            }

            // Удаляем связь пользователя с песней
            $entityManager->remove($userSong);
            
            // Удаляем саму песню, если она не связана с другими пользователями
            $otherUserSongs = $userSongRepository->createQueryBuilder('us')
                ->where('us.song = :songId')
                ->andWhere('us.user != :userId')
                ->setParameter('songId', $songId)
                ->setParameter('userId', $user->getId())
                ->getQuery()
                ->getResult();

            if (empty($otherUserSongs)) {
                $entityManager->remove($song);
            }
            
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Song deleted successfully'
            ], Response::HTTP_OK);

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