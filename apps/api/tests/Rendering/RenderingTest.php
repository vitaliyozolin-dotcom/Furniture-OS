<?php

declare(strict_types=1);

namespace App\Tests\Rendering;

use App\Rendering\Application\Generation;
use App\Rendering\Application\Image;
use App\Rendering\Application\ImageGenerator;
use App\Rendering\Application\RenderCabinet;
use App\Rendering\Application\RenderRequest;
use App\Rendering\Infrastructure\LocalRenderJournal;
use PHPUnit\Framework\TestCase;

final class RenderingTest extends TestCase
{
    public const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=';

    public function testPendingJobResumesAndCompletedRequestIsCached(): void
    {
        $journal = new LocalRenderJournal(sys_get_temp_dir().'/render-'.bin2hex(random_bytes(8)));
        $generator = $this->createMock(ImageGenerator::class);
        $generator->method('name')->willReturn('nano_banana');
        $generator->method('model')->willReturn('test-model');
        $generator->expects(self::once())->method('start')->willReturn(new Generation('job-1', 'processing'));
        $generator->expects(self::once())->method('poll')->with('job-1')->willReturn(new Generation('job-1', 'completed', $this->image(), 315));
        $useCase = new RenderCabinet([$generator], $journal, true, 'nano_banana', 1000, 2000);
        $request = new RenderRequest($this->image(), 'Матовый белый корпус');
        self::assertSame('processing', $useCase->advance($request)['status']);
        self::assertSame('completed', $useCase->advance($request)['status']);
        $cached = $useCase->advance($request);
        self::assertSame(315, $cached['costKopecks']);
        self::assertSame(base64_decode(self::PNG), $useCase->image($cached['key'])->bytes);
    }

    public function testUncertainSubmissionNeverCreatesAnotherPaidRequest(): void
    {
        $generator = $this->createMock(ImageGenerator::class);
        $generator->method('name')->willReturn('nano_banana');
        $generator->method('model')->willReturn('test-model');
        $generator->expects(self::once())->method('start')->willThrowException(new \RuntimeException('transport'));
        $useCase = new RenderCabinet([$generator], new LocalRenderJournal(sys_get_temp_dir().'/render-'.bin2hex(random_bytes(8))), true, 'nano_banana', 1000, 2000);
        $request = new RenderRequest($this->image(), 'Белый шкаф');
        try {
            $useCase->advance($request);
            self::fail('Expected transport failure');
        } catch (\RuntimeException $e) {
            self::assertSame('transport', $e->getMessage());
        }
        $this->expectExceptionMessage('uncertain');
        $useCase->advance($request);
    }

    private function image(): Image
    {
        return new Image(base64_decode(self::PNG));
    }
}
