<?php

namespace App\Controller\ListeningHistory;

use App\Controller\DefaultController;
use App\Entity\FavoriteSong;
use App\Entity\ListeningHistory;
use App\Entity\ReleaseSong;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use getID3;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ListeningHistoryController extends DefaultController
{
    public function history(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getAuthUser($request, $userRepository);

        $listeningHistory = $entityManager->getRepository(ListeningHistory::class)
            ->findBy(['user' => $user], ['listenedAt' => 'DESC']);

        $historyData = [];
        foreach ($listeningHistory as $historyEntry) {
            $song = $historyEntry->getSong();

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
            
            $filePath = sprintf('%d/%d/song.mp3', $song->getSingers()->first()->getId(), $song->getId());
            $absolutePath = $this->getParameter('upload_directory') . '/' . $filePath;
            $duration = 0;

            if (file_exists($absolutePath)) {
                $getID3 = new getID3();
                $fileInfo = $getID3->analyze($absolutePath);
                $duration = isset($fileInfo['playtime_seconds']) ? round($fileInfo['playtime_seconds']) : 0;
            }

            $historyData[] = [
                'id' => $song->getId(),
                'title' => $song->getTitle(),
                'playCount' => $song->getPlayCount(),
                'createdAt' => $song->getCreatedAt()->format('Y-m-d H:i:s'),
                'singers' => $singersData,
                'genres' => $genresData,
                'isFavorite' => $isFavorite,
                'duration' => $duration,
                'release' => $releaseData,
                'listenedAt' => $historyEntry->getListenedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json([
            'success' => true,
            'data' => [
                'songs' => $historyData,
            ],
        ]);
    }
} 