<?php

declare(strict_types=1);

namespace App\Platform\Presentation;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class HttpSubscriber implements EventSubscriberInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['request', 100], KernelEvents::EXCEPTION => ['exception', 100], KernelEvents::RESPONSE => ['response', -100]];
    }
    public function request(RequestEvent $event): void
    {
        $id = $event->getRequest()->headers->get('X-Correlation-ID', '');
        $event->getRequest()->attributes->set('correlationId', preg_match('/^[a-zA-Z0-9-]{1,64}$/D', $id) ? $id : bin2hex(random_bytes(16)));
    }
    public function exception(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
        $id = $event->getRequest()->attributes->get('correlationId');
        $this->logger->error('http_error', ['status' => $status, 'correlationId' => $id, 'exception' => $exception]);
        $event->setResponse(new JsonResponse([
            'type' => 'about:blank', 'title' => $status >= 500 ? 'Service unavailable' : 'Request rejected',
            'status' => $status, 'detail' => $status >= 500 ? 'The request could not be completed.' : $exception->getMessage(),
            'correlationId' => $id,
        ], $status, array_merge($exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [], ['Content-Type' => 'application/problem+json'])));
    }
    public function response(ResponseEvent $event): void
    {
        $event->getResponse()->headers->set('X-Correlation-ID', (string) $event->getRequest()->attributes->get('correlationId'));
        $event->getResponse()->headers->set('Cache-Control', 'no-store');
        $event->getResponse()->headers->set('X-Content-Type-Options', 'nosniff');
        $this->logger->info('http_response', ['method' => $event->getRequest()->getMethod(), 'path' => $event->getRequest()->getPathInfo(), 'status' => $event->getResponse()->getStatusCode(), 'correlationId' => $event->getRequest()->attributes->get('correlationId')]);
    }
}
