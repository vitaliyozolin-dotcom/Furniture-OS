<?php

declare(strict_types=1);

namespace App\Identity\Presentation;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\HttpFoundation\Response;

final readonly class AuthGuard implements EventSubscriberInterface
{
    public function __construct(private CsrfTokenManagerInterface $csrf)
    {
    }
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['guard', 10], LogoutEvent::class => 'logout'];
    }
    public function guard(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !str_starts_with($request->getPathInfo(), '/api/') || in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }
        if (strlen($request->getContent()) > 65536) {
            throw new HttpException(413, 'JSON body exceeds 64 KiB.');
        }
        if (in_array($request->getPathInfo(), ['/api/prototype/v0/calculate', '/api/cabinets/preview'], true)) {
            return;
        }
        if (!$this->csrf->isTokenValid(new CsrfToken('pilot', $request->headers->get('X-CSRF-Token', '')))) {
            throw new AccessDeniedHttpException('Обновите страницу и повторите запрос (CSRF).');
        }
    }
    public function logout(LogoutEvent $event): void
    {
        $event->setResponse(new Response('', 204));
    }
}
