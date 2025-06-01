<?php

namespace App\Controller\Song;

use App\Controller\DefaultController;
use App\Dto\UploadSongDto;
use App\Entity\Song;
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
        $genreTitles = explode(',', $request->request->get('genres'));
        $fileUploadDto = new UploadSongDto(
            $songTitle,
            $songFile,
            $songImgFile,
            $genreTitles
        );

        $errors = $validator->validate($fileUploadDto);
        if (count($errors) > 0) {
            return $this->errorValidationResponse($errors);
        }

        $song = new Song();
        $song->setTitle($songTitle);
        $song->addSinger($user->getSinger());

        foreach ($genreTitles as $genreTitle) {
            $genre = $genreRepository->findOneBy(['title' => $genreTitle]);
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

        return $this->json([
            'success' => true,
            'data' => [
                'message' => 'Song uploaded successfully',
                'songId' => $song->getId(),
            ],
        ]);
    }

    public function getLatestSongs(
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $songs = $entityManager->getRepository(Song::class)
            ->createQueryBuilder('s')
            ->leftJoin('s.singers', 'singers')
            ->leftJoin('s.genres', 'genres')
            ->select('s', 'singers', 'genres')
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

            $songsData[] = [
                'id' => $song->getId(),
                'title' => $song->getTitle(),
                'playCount' => $song->getPlayCount(),
                'createdAt' => $song->getCreatedAt()->format('Y-m-d H:i:s'),
                'singers' => $singersData,
                'genres' => $genresData,
            ];
        }

        return $this->json([
            'success' => true,
            'data' => [
                'songs' => $songsData,
            ],
        ]);
    }
}

// http://localhost:5173/register
