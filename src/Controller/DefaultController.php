<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class DefaultController extends AbstractController
{
    protected function errorValidationResponse(ConstraintViolationListInterface $errors): JsonResponse
    {
        $errorMessages = [];

        foreach ($errors as $error) {
            $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
        }

        return $this->json([
            'success' => false,
            'error' => [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Error validation',
                'errors' => $errorMessages
            ]
        ], Response::HTTP_BAD_REQUEST);
    }

    protected function getAuthUser(
        Request $request,
        UserRepository $userRepository,
    ): User
    {
        $authorizationHeader = $request->headers->get('Authorization');
        $token = substr($authorizationHeader, 7);

        /** @var User $user */
        $user = $userRepository->findOneBy(['token' => $token]);

        return $user;
    }
}
