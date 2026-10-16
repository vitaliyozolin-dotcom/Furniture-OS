<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Identity\Presentation\AuthGuard;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final class PilotGuardTest extends TestCase
{
    public static function exempt(): iterable
    {
        yield ['/api/auth/session', 'GET', HttpKernelInterface::MAIN_REQUEST];
        yield ['/api/auth/session', 'HEAD', HttpKernelInterface::MAIN_REQUEST];
        yield ['/api/projects', 'OPTIONS', HttpKernelInterface::MAIN_REQUEST];
        yield ['/health/live', 'POST', HttpKernelInterface::MAIN_REQUEST];
        yield ['/api/projects', 'POST', HttpKernelInterface::SUB_REQUEST];
        yield ['/api/cabinets/preview', 'POST', HttpKernelInterface::MAIN_REQUEST];
        yield ['/api/prototype/v0/calculate', 'POST', HttpKernelInterface::MAIN_REQUEST];
    }
    #[DataProvider('exempt')]
    public function testOnlyMutationsNeedCsrf(string $path, string $method, int $kind): void
    {
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->expects(self::never())->method('isTokenValid');
        (new AuthGuard($csrf))->guard(new RequestEvent($this->createMock(HttpKernelInterface::class), Request::create($path, $method), $kind));
        self::assertNotEmpty(AuthGuard::getSubscribedEvents());
    }
    public function testValidMutationAndLogout(): void
    {
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->expects(self::once())->method('isTokenValid')->willReturn(true);
        $guard = new AuthGuard($csrf);
        $request = Request::create('/api/projects', 'POST', server: ['HTTP_X_CSRF_TOKEN' => 'test-token']);
        $guard->guard(new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST));
        $event = new LogoutEvent($request, null);
        $guard->logout($event);
        self::assertSame(204, $event->getResponse()->getStatusCode());
    }
    public function testInvalidMutationFailsClosed(): void
    {
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(false);
        $this->expectExceptionCode(0);
        $this->expectException(HttpException::class);
        (new AuthGuard($csrf))->guard(new RequestEvent($this->createMock(HttpKernelInterface::class), Request::create('/api/projects', 'POST'), HttpKernelInterface::MAIN_REQUEST));
    }
    public function testOversizedBodyRejectedBeforeCsrf(): void
    {
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->expects(self::never())->method('isTokenValid');
        try {
            (new AuthGuard($csrf))->guard(new RequestEvent($this->createMock(HttpKernelInterface::class), Request::create('/api/projects', 'POST', content: str_repeat('x', 65537)), HttpKernelInterface::MAIN_REQUEST));
            self::fail('Expected body limit');
        } catch (HttpException $exception) {
            self::assertSame(413, $exception->getStatusCode());
        }
    }
}
