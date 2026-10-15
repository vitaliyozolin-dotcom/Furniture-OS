<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Platform\Infrastructure\PostgresReadiness;
use App\Platform\Presentation\HealthController;
use App\Platform\Presentation\HttpSubscriber;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class PlatformTest extends TestCase
{
    public function testSubscriberCarriesRequestCorrelationThroughRegisteredEvents(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/health/live');
        $request->headers->set('X-Correlation-ID', 'registered-events');
        $dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
        $dispatcher->addSubscriber(new HttpSubscriber(new NullLogger()));
        $dispatcher->dispatch(new \Symfony\Component\HttpKernel\Event\RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST), \Symfony\Component\HttpKernel\KernelEvents::REQUEST);
        $response = new \Symfony\Component\HttpFoundation\JsonResponse(['status' => 'ok']);
        $dispatcher->dispatch(new \Symfony\Component\HttpKernel\Event\ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response), \Symfony\Component\HttpKernel\KernelEvents::RESPONSE);
        self::assertSame('registered-events', $response->headers->get('X-Correlation-ID'));
        self::assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }
    public function testUnavailableDatabaseProducesReadiness503(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('fetchOne')->willThrowException(new \RuntimeException('connection failed'));
        $controller = new HealthController(new PostgresReadiness($connection));
        self::assertSame(503, $controller->ready()->getStatusCode());
    }
    public function testUnexpectedErrorIsRedacted(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/api/prototype/v0/calculate');
        $request->attributes->set('correlationId', 'redaction-test');
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, new \RuntimeException('private-secret'));
        (new HttpSubscriber(new NullLogger()))->exception($event);
        self::assertSame(500, $event->getResponse()->getStatusCode());
        self::assertStringNotContainsString('private-secret', $event->getResponse()->getContent());
        self::assertStringContainsString('redaction-test', $event->getResponse()->getContent());
    }
}
