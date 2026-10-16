<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final class LoginHandler implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface
{
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        /** @var SecurityAccount $user */
        $user = $token->getUser();
        return new JsonResponse($user->account->profile());
    }
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $status = $exception instanceof \Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException ? 429 : 401;
        return new JsonResponse(['type' => 'about:blank', 'title' => 'Sign in rejected', 'status' => $status, 'detail' => $status === 429 ? 'Слишком много попыток. Попробуйте позже.' : 'Неверный email или пароль.', 'correlationId' => (string) $request->attributes->get('correlationId')], $status, ['Content-Type' => 'application/problem+json']);
    }
}
