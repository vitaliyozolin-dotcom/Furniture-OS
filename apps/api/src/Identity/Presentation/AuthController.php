<?php

declare(strict_types=1);

namespace App\Identity\Presentation;

use App\Identity\Application\RegisterAccount;
use App\Identity\Application\Contract\CurrentActor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class AuthController
{
    public function __construct(private RegisterAccount $register, private CurrentActor $actor, private CsrfTokenManagerInterface $csrf, private RateLimiterFactory $registrationLimiter)
    {
    }
    #[Route('/api/auth/session', methods: ['GET'])]
    public function session(): JsonResponse
    {
        return new JsonResponse(['user' => $this->actor->profile(), 'csrfToken' => $this->csrf->getToken('pilot')->getValue()]);
    }
    #[Route('/api/auth/register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        if (!$this->registrationLimiter->create($request->getClientIp() ?? 'unknown')->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException(900, 'Слишком много регистраций. Попробуйте позже.');
        }
        try {
            $data = $request->toArray();
        } catch (\Symfony\Component\HttpFoundation\Exception\JsonException $e) {
            throw new BadRequestHttpException('Некорректный JSON.', $e);
        }
        foreach (['name', 'email', 'password'] as $field) {
            if (!isset($data[$field]) || !is_string($data[$field])) {
                throw new \InvalidArgumentException('Заполните имя, email и пароль.');
            }
        }
        return new JsonResponse($this->register->register($data['name'], $data['email'], $data['password']), 201);
    }
    #[Route('/api/auth/login', methods: ['POST'])]
    public function login(): Response
    {
        return new JsonResponse(['detail' => 'Неверный запрос входа.'], 401);
    }
    #[Route('/api/auth/logout', methods: ['POST'])]
    public function logout(): Response
    {
        return new Response('', 204);
    }
}
