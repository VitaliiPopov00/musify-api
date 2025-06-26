<?php

namespace App\Controller\Admin;

use App\Controller\DefaultController;
use App\Repository\UserRepository;
use App\Repository\SingerRepository;
use App\Repository\SongRepository;
use App\Repository\GenreRepository;
use App\Repository\CustomGenreRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends DefaultController
{
    public function userIsAdmin(
        Request $request,
        UserRepository $userRepository,
    ) {
        $user = $this->getAuthUser($request, $userRepository);

        return $user->getIsAdmin();
    }

    public function statistics(
        Request $request,
        UserRepository $userRepository,
        SingerRepository $singerRepository,
        SongRepository $songRepository,
        GenreRepository $genreRepository,
        CustomGenreRepository $customGenreRepository,
    ): JsonResponse {
        if (!$this->userIsAdmin($request, $userRepository)) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Access denied. Admin privileges required.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        $statistics = [
            'users_count' => $userRepository->count([]),
            'singers_count' => $singerRepository->count([]),
            'songs_count' => $songRepository->count([]),
            'genres_count' => $genreRepository->count([]),
            'custom_genres_count' => $customGenreRepository->count([]),
        ];

        return $this->json([
            'success' => true,
            'data' => $statistics
        ]);
    }
}