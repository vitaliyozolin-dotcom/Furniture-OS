<?php

declare(strict_types=1);

namespace App\Tests\Rendering;

use App\Rendering\Application\Generation;
use App\Rendering\Application\Image;
use App\Rendering\Application\ImageGenerator;
use App\Rendering\Application\RenderCabinet;
use App\Rendering\Infrastructure\LocalRenderJournal;
use App\Rendering\Infrastructure\PolzaNanoBanana;
use App\Rendering\Presentation\RenderCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class RenderCommandTest extends TestCase
{
    public function testCliEndToEndSubmitResumeSaveAndCacheUsingHttpContract(): void
    {
        $root = $this->root();
        $source = $root.'/reference.png';
        file_put_contents($source, base64_decode(RenderingTest::PNG));
        $http = new MockHttpClient([
            new MockResponse('{"id":"job-1","status":"pending"}'),
            new MockResponse('{"id":"job-1","status":"completed","data":{"url":"https://s3.polza.ai/render.png"},"usage":{"cost_rub":3.15}}'),
        ]);
        $downloads = new MockHttpClient(new MockResponse(base64_decode(RenderingTest::PNG)));
        $provider = new PolzaNanoBanana($http, $downloads, 'test-key', 'google/gemini-nano-banana-2.1', 's3.polza.ai');
        $tester = new CommandTester(new RenderCommand(new RenderCabinet([$provider], new LocalRenderJournal($root), true, 'nano_banana', 1000, 2000)));
        $arguments = ['reference' => $source, 'instructions' => 'Дуб', '--wait' => '0', '--output' => $root.'/concept.png'];
        self::assertSame(2, $tester->execute($arguments));
        self::assertFileDoesNotExist($root.'/concept.png');
        self::assertSame(0, $tester->execute($arguments));
        self::assertSame(base64_decode(RenderingTest::PNG), file_get_contents($root.'/concept.png'));
        $record = json_decode($tester->getDisplay(), true, flags: JSON_THROW_ON_ERROR);
        self::assertTrue($record['conceptual']);
        self::assertSame(315, $record['costKopecks']);
        self::assertSame('RUB', $record['currency']);
        self::assertSame('job-1', $record['providerJobId']);
        self::assertSame(0, $record['retryCount']);
        self::assertArrayHasKey('elapsedMs', $record);
        self::assertStringNotContainsString('test-key', file_get_contents($root.'/journal.json'));
        self::assertStringNotContainsString('https://', file_get_contents($root.'/journal.json'));
        $arguments['--output'] = $root.'/cached.png';
        self::assertSame(0, $tester->execute($arguments));
        self::assertSame(2, $http->getRequestsCount());
        self::assertSame(1, $downloads->getRequestsCount());
        self::assertSame(1, $tester->execute($arguments));
        self::assertStringContainsString('new file', $tester->getDisplay());
    }

    public static function states(): iterable
    {
        yield ['completed', 0];
        yield ['failed', 1];
        yield ['cancelled', 1];
        yield ['processing', 2];
    }

    #[DataProvider('states')]
    public function testCliStatusExitCodesWithoutExport(string $status, int $exit): void
    {
        $root = $this->root();
        file_put_contents($root.'/source.png', base64_decode(RenderingTest::PNG));
        $provider = $this->createMock(ImageGenerator::class);
        $provider->method('name')->willReturn('fake');
        $provider->method('model')->willReturn('test');
        $provider->method('start')->willReturn(new Generation('job', $status, $status === 'completed' ? new Image(base64_decode(RenderingTest::PNG)) : null));
        $tester = new CommandTester(new RenderCommand(new RenderCabinet([$provider], new LocalRenderJournal($root), true, 'fake', 1000, 2000)));
        self::assertSame($exit, $tester->execute(['reference' => $root.'/source.png', 'instructions' => 'Дуб', '--wait' => 0]));
        self::assertSame($status, json_decode($tester->getDisplay(), true, flags: JSON_THROW_ON_ERROR)['status']);
    }

    public function testCliWaitsForExistingJob(): void
    {
        $root = $this->root();
        file_put_contents($root.'/source.png', base64_decode(RenderingTest::PNG));
        $provider = $this->createMock(ImageGenerator::class);
        $provider->method('name')->willReturn('fake');
        $provider->method('model')->willReturn('test');
        $provider->expects(self::once())->method('start')->willReturn(new Generation('job', 'pending'));
        $provider->expects(self::once())->method('poll')->willReturn(new Generation('job', 'completed', new Image(base64_decode(RenderingTest::PNG))));
        $tester = new CommandTester(new RenderCommand(new RenderCabinet([$provider], new LocalRenderJournal($root), true, 'fake', 1000, 2000)));
        self::assertSame(0, $tester->execute(['reference' => $root.'/source.png', 'instructions' => 'Дуб', '--wait' => 3]));
    }

    public function testCliWaitDeadlineReturnsPendingWithoutResubmitting(): void
    {
        $root = $this->root();
        file_put_contents($root.'/source.png', base64_decode(RenderingTest::PNG));
        $provider = $this->createMock(ImageGenerator::class);
        $provider->method('name')->willReturn('fake');
        $provider->method('model')->willReturn('test');
        $provider->expects(self::once())->method('start')->willReturn(new Generation('job', 'pending'));
        $provider->expects(self::never())->method('poll');
        $tester = new CommandTester(new RenderCommand(new RenderCabinet([$provider], new LocalRenderJournal($root), true, 'fake', 1000, 2000)));
        self::assertSame(2, $tester->execute(['reference' => $root.'/source.png', 'instructions' => 'Дуб', '--wait' => 1]));
    }

    public static function invalidArguments(): iterable
    {
        yield ['--wait', '-1', '--wait'];
        yield ['--wait', '601', '--wait'];
        yield ['--wait', 'abc', '--wait'];
        yield ['reference', '/does-not-exist.png', 'Reference'];
        yield ['--output', '/does-not-exist/folder/out.png', '--output'];
        yield ['--resolution', '8K', 'resolution'];
        yield ['instructions', '', 'instructions'];
    }

    #[DataProvider('invalidArguments')]
    public function testInvalidCliInputDoesNotCallProvider(string $option, string $value, string $message): void
    {
        $root = $this->root();
        file_put_contents($root.'/source.png', base64_decode(RenderingTest::PNG));
        $provider = $this->createMock(ImageGenerator::class);
        $provider->expects(self::never())->method('start');
        $tester = new CommandTester(new RenderCommand(new RenderCabinet([$provider], new LocalRenderJournal($root), true, 'fake', 1000, 2000)));
        self::assertSame(1, $tester->execute(array_replace(['reference' => $root.'/source.png', 'instructions' => 'Дуб', '--wait' => 0], [$option => $value])));
        self::assertStringContainsString($message, $tester->getDisplay());
    }

    public function testInvalidPngDoesNotMakePaidCall(): void
    {
        $root = $this->root();
        file_put_contents($root.'/source.png', 'not png');
        $provider = $this->createMock(ImageGenerator::class);
        $provider->expects(self::never())->method('start');
        $tester = new CommandTester(new RenderCommand(new RenderCabinet([$provider], new LocalRenderJournal($root), true, 'fake', 1000, 2000)));
        self::assertSame(1, $tester->execute(['reference' => $root.'/source.png', 'instructions' => 'Дуб']));
        self::assertStringContainsString('PNG', $tester->getDisplay());
    }

    private function root(): string
    {
        $root = sys_get_temp_dir().'/render-command-'.bin2hex(random_bytes(8));
        mkdir($root, 0700, true);
        return $root;
    }
}
