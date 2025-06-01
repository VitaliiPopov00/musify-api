<?php

namespace App\Controller\User;

use App\Controller\DefaultController;
use App\Dto\LoginDto;
use App\Dto\RegisterDto;
use App\Entity\User;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends DefaultController
{
    public function create(
        Request $request,
        RoleRepository $roleRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse
    {
        /** @var RegisterDto $registerDto */
        $registerDto = $serializer->deserialize(
            data: $request->getContent(),
            type: RegisterDto::class,
            format: 'json'
        );

        $errors = $validator->validate($registerDto);

        if (count($errors) > 0) {
            return $this->errorValidationResponse($errors);
        }

        $user = new User();
        $user->setLogin($registerDto->login);
        $user->setPassword($passwordHasher->hashPassword($user, $registerDto->password));
        $user->setRole($roleRepository->getUserRole());

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'data' => [
                'message' => 'User created successfully',
                'userId' => $user->getId(),
            ],
        ]);
    }

    public function login(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse
    {
        /** @var LoginDto $loginDto */
        $loginDto = $serializer->deserialize(
            data: $request->getContent(),
            type: LoginDto::class,
            format: 'json'
        );

        $errors = $validator->validate($loginDto);
        if (count($errors) > 0) {
            return $this->errorValidationResponse($errors);
        }

        /** @var User|Null $user */
        $user = $userRepository->findOneBy(['login' => $loginDto->login]);

        if (!$user) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'message' => sprintf('User with login "%s" not found', $loginDto->login),
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$passwordHasher->isPasswordValid($user, $loginDto->password)) {
            return $this->json([
                'success' => false,
                'error' => [
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'message' => 'Incorrect password',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user->generateToken();
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'data' => [
                'token' => $user->getToken(),
                'user' => [
                    'id' => $user->getId(),
                    'login' => $user->getLogin(),
                ],
            ],
        ]);
    }

    public function logout(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse
    {
        $user = $this->getAuthUser($request, $userRepository);
        $user->clearToken();

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'data' => [
                'message' => 'Logout is successful',
            ],
        ]);
    }
}
