<?php

namespace App\Controller\Admin\Genre;

use App\Controller\Admin\AdminController;
use App\Repository\UserRepository;
use App\Repository\GenreRepository;
use App\Repository\CustomGenreRepository;
use App\Repository\SongRepository;
use App\Repository\SingerRepository;
use App\Dto\CreateGenreDto;
use App\Dto\DeleteCustomGenreDto;
use App\Dto\PromoteCustomGenreDto;
use App\Entity\Genre;
use App\Entity\Song;
use App\Entity\Singer;
use App\Enum\CustomGenreEnum;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class GenreController extends AdminController
{
    public function genres(
        Request $request,
        UserRepository $userRepository,
        GenreRepository $genreRepository,
        CustomGenreRepository $customGenreRepository,
    ): JsonResponse
    {
        // Проверяем, что пользователь является администратором
        if (!$this->userIsAdmin($request, $userRepository)) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Access denied. Admin privileges required.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        // Получаем все жанры
        $genres = $genreRepository->findAll();
        $genresData = array_map(function ($genre) {
            return [
                'id' => $genre->getId(),
                'title' => $genre->getTitle(),
            ];
        }, $genres);

        // Получаем кастомные жанры с группировкой
        $connection = $customGenreRepository->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                LOWER(cg.title) as title, 
                COUNT(cg.id) as count, 
                GROUP_CONCAT(cg.id) as ids
            FROM custom_genre cg
            GROUP BY title
            ORDER BY title ASC
        ";
        
        $customGenresResult = $connection->executeQuery($sql)->fetchAllAssociative();

        $customGenresData = array_map(function ($item) {
            return [
                'title' => $item['title'],
                'count' => (int) $item['count'],
                'ids' => array_map('intval', explode(',', $item['ids'])),
            ];
        }, $customGenresResult);

        return $this->json([
            'success' => true,
            'data' => [
                'genres' => $genresData,
                'custom_genres' => $customGenresData,
            ],
        ]);
    }

    public function createGenre(
        Request $request,
        UserRepository $userRepository,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
    ): JsonResponse
    {
        if (!$this->userIsAdmin($request, $userRepository)) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Access denied. Admin privileges required.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        $dto = $serializer->deserialize(
            $request->getContent(),
            CreateGenreDto::class,
            'json'
        );

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->errorValidationResponse($errors);
        }

        $genre = new Genre();
        $genre->setTitle($dto->title);
        $genre->setCreatedAt(new \DateTimeImmutable());
        $genre->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($genre);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $genre->getId(),
                'title' => $genre->getTitle(),
                'created_at' => $genre->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $genre->getUpdatedAt()->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function deleteGenre(
        int $id,
        Request $request,
        UserRepository $userRepository,
        GenreRepository $genreRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse
    {
        if (!$this->userIsAdmin($request, $userRepository)) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Access denied. Admin privileges required.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        $genre = $genreRepository->find($id);

        if (!$genre) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Genre not found',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        $songs = $genre->getSongs();
        foreach ($songs as $song) {
            $genre->removeSong($song);
        }

        $singers = $genre->getSingers();
        foreach ($singers as $singer) {
            $genre->removeSinger($singer);
        }

        $entityManager->remove($genre);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'data' => [
                'message' => 'Genre deleted successfully',
            ],
        ]);
    }

    public function deleteCustomGenre(
        Request $request,
        UserRepository $userRepository,
        CustomGenreRepository $customGenreRepository,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
    ): JsonResponse
    {
        if (!$this->userIsAdmin($request, $userRepository)) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Access denied. Admin privileges required.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        $dto = $serializer->deserialize(
            $request->getContent(),
            DeleteCustomGenreDto::class,
            'json'
        );

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->errorValidationResponse($errors);
        }

        $customGenres = $customGenreRepository->findByTitleIgnoreCase($dto->title);

        if (empty($customGenres)) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Custom genres with this title not found',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        $deletedCount = 0;
        foreach ($customGenres as $customGenre) {
            $entityManager->remove($customGenre);
            $deletedCount++;
        }

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'data' => [
                'message' => 'Custom genres deleted successfully',
                'deleted_count' => $deletedCount,
                'title' => $dto->title,
            ],
        ]);
    }

    public function promoteCustomGenre(
        Request $request,
        UserRepository $userRepository,
        CustomGenreRepository $customGenreRepository,
        GenreRepository $genreRepository,
        SongRepository $songRepository,
        SingerRepository $singerRepository,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
    ): JsonResponse
    {
        if (!$this->userIsAdmin($request, $userRepository)) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Access denied. Admin privileges required.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        $dto = $serializer->deserialize(
            $request->getContent(),
            PromoteCustomGenreDto::class,
            'json'
        );

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->errorValidationResponse($errors);
        }

        // Ищем кастомные жанры с таким названием (регистронезависимый поиск)
        $customGenres = $customGenreRepository->findByTitleIgnoreCase($dto->title);

        if (empty($customGenres)) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Custom genres with this title not found',
                ],
            ], Response::HTTP_NOT_FOUND);
        }

        // Проверяем, существует ли уже жанр с таким названием
        $existingGenre = $genreRepository->findOneByTitleIgnoreCase($dto->title);
        
        if ($existingGenre) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_CONFLICT,
                    'message' => 'Genre with this title already exists',
                ],
            ], Response::HTTP_CONFLICT);
        }

        // Создаем новый жанр
        $genre = new Genre();
        $genre->setTitle($dto->title);
        $genre->setCreatedAt(new \DateTimeImmutable());
        $genre->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($genre);
        $entityManager->flush();

        $processedCount = 0;
        $deletedCount = 0;

        // Обрабатываем каждую запись кастомного жанра
        foreach ($customGenres as $customGenre) {
            $entityType = $customGenre->getEntityType();
            $entityId = $customGenre->getEntityId();

            // В зависимости от типа сущности добавляем связи
            if ($entityType === CustomGenreEnum::song->value) {
                $song = $songRepository->find($entityId);
                if ($song) {
                    $song->addGenre($genre);
                    $processedCount++;
                }
            } elseif ($entityType === CustomGenreEnum::singer->value) {
                $singer = $singerRepository->find($entityId);
                if ($singer) {
                    $singer->addGenre($genre);
                    $processedCount++;
                }
            }

            // Удаляем кастомный жанр
            $entityManager->remove($customGenre);
            $deletedCount++;
        }

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'data' => [
                'message' => 'Custom genre promoted to main genre successfully',
                'genre' => [
                    'id' => $genre->getId(),
                    'title' => $genre->getTitle(),
                    'created_at' => $genre->getCreatedAt()->format('Y-m-d H:i:s'),
                ],
                'processed_entities' => $processedCount,
                'deleted_custom_genres' => $deletedCount,
            ],
        ]);
    }
}