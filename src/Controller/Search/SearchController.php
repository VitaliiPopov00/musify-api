<?php

namespace App\Controller\Search;

use App\Controller\DefaultController;
use App\Repository\ReleaseRepository;
use App\Repository\SongRepository;
use App\Repository\SingerRepository;
use App\Repository\UserRepository;
use App\Entity\FavoriteSong;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class SearchController extends DefaultController
{
    public function __construct(
        private readonly ReleaseRepository $releaseRepository,
        private readonly SongRepository $songRepository,
        private readonly SingerRepository $singerRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function search(
        Request $request,
        UserRepository $userRepository,
    ): JsonResponse
    {
        $query = $request->query->get('query', '');
        $user = $this->getAuthUser($request, $userRepository);

        // Поиск релизов
        $releases = $this->releaseRepository->createQueryBuilder('r')
            ->where('r.title LIKE :query')
            ->andWhere('r.isReleased = 1')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();

        $releasesResult = [];
        foreach ($releases as $release) {
            $firstSong = $release->getReleaseSongs()->first();
            $firstSinger = $release->getReleaseSingers()->first();
            
            $releasesResult[] = [
                'id' => $release->getId(),
                'title' => $release->getTitle(),
                'firstSingerId' => $firstSinger ? $firstSinger->getSinger()->getId() : null,
                'firstSingerTitle' => $firstSinger ? $firstSinger->getSinger()->getName() : null,
                'firstSongId' => $firstSong ? $firstSong->getSong()->getId() : null,
            ];
        }

        // Поиск песен
        $songs = $this->songRepository->createQueryBuilder('s')
            ->leftJoin('App\Entity\ReleaseSong', 'rs', 'WITH', 'rs.song = s')
            ->leftJoin('rs.release', 'r')
            ->where('s.title LIKE :query')
            ->andWhere('r.isReleased = 1 OR r.id IS NULL')
            ->andWhere('s.isUserSong = 0')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();

        $songsResult = [];
        foreach ($songs as $song) {
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

            $songsResult[] = [
                'id' => $song->getId(),
                'title' => $song->getTitle(),
                'genres' => $genres,
                'singers' => $songSingers,
                'isFavorite' => $isFavorite,
                'duration' => $duration,
                'playCount' => $song->getPlayCount(),
            ];
        }

        // Поиск исполнителей
        $singers = $this->singerRepository->createQueryBuilder('s')
            ->where('s.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();

        $singersResult = [];
        foreach ($singers as $singer) {
            $singersResult[] = [
                'id' => $singer->getId(),
                'name' => $singer->getName(),
            ];
        }

        return $this->json([
            'success' => true,
            'data' => [
                'releases' => $releasesResult,
                'songs' => $songsResult,
                'singers' => $singersResult,
            ],
        ]);
    }
}
