<?php

namespace App\Controller\Subscribe;

use App\Controller\DefaultController;
use App\Dto\CreateSubscribeDto;
use App\Entity\Subscribe;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SubscribeController extends DefaultController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function subscribe(
        int $singerId,
        Request $request,
        UserRepository $userRepository,
    ): JsonResponse
    {
        $dto = new CreateSubscribeDto();
        $dto->setSingerId($singerId);
        $dto->setUserId($this->getAuthUser($request, $userRepository)->getId());

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->errorValidationResponse($errors);
        }

        // Проверяем существующую подписку
        $existingSubscribe = $this->entityManager->getRepository(Subscribe::class)->findOneBy([
            'singerId' => $dto->getSingerId(),
            'userId' => $dto->getUserId()
        ]);

        if ($existingSubscribe) {
            return $this->json([
                'success' => false,
                'data' => [
                    'message' => 'Subscribe is already exists'
                ],
            ], 400);
        }

        $subscribe = new Subscribe();
        $subscribe->setSingerId($dto->getSingerId());
        $subscribe->setUserId($dto->getUserId());

        $this->entityManager->persist($subscribe);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'data' => [
                'message' => 'Successfully subscribed'
            ],
        ]);
    }

    public function unsubscribe(
        int $singerId,
        Request $request,
        UserRepository $userRepository,
    ): JsonResponse
    {
        $userId = $this->getAuthUser($request, $userRepository)->getId();

        $subscribe = $this->entityManager->getRepository(Subscribe::class)->findOneBy([
            'singerId' => $singerId,
            'userId' => $userId
        ]);

        if (!$subscribe) {
            return $this->json([
                'success' => false,
                'data' => [
                    'message' => 'Subscribe is not exists'
                ],
            ], 400);
        }

        $this->entityManager->remove($subscribe);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'data' => [
                'message' => 'Successfully unsubscribed'
            ],
        ]);
    }
}
