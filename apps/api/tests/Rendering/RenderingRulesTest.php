<?php

declare(strict_types=1);

namespace App\Tests\Rendering;

use App\Rendering\Application\Generation;
use App\Rendering\Application\Image;
use App\Rendering\Application\ImageGenerator;
use App\Rendering\Application\RenderCabinet;
use App\Rendering\Application\RenderRequest;
use App\Rendering\Infrastructure\LocalRenderJournal;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RenderingRulesTest extends TestCase
{
    public static function invalidImages(): iterable
    {
        yield [''];
        yield ['not PNG'];
        yield [str_repeat('x', 10485761)];
        yield [base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==')];
        $bytes = base64_decode(RenderingTest::PNG);
        yield [substr_replace($bytes, pack('NN', 10000, 10000), 16, 8)];
    }

    #[DataProvider('invalidImages')]
    public function testInvalidReferenceRejected(string $bytes): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Image($bytes);
    }

    public static function badRequests(): iterable
    {
        yield ['', '1K'];
        yield ['   ', '1K'];
        yield [str_repeat('a', 12001), '1K'];
        yield ['test', '8K'];
    }

    #[DataProvider('badRequests')]
    public function testInvalidInstructionsAndResolution(string $prompt, string $resolution): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RenderRequest($this->image(), $prompt, $resolution);
    }

    public function testCacheIdentityIncludesModelProviderInstructionsResolutionAndReference(): void
    {
        $request = new RenderRequest($this->image(), 'Белый');
        $key = $request->key('provider', 'model');
        self::assertSame($key, (new RenderRequest($this->image(), 'Белый'))->key('provider', 'model'));
        self::assertNotSame($key, $request->key('other', 'model'));
        self::assertNotSame($key, $request->key('provider', 'other'));
        self::assertNotSame($key, (new RenderRequest($this->image(), 'Дуб'))->key('provider', 'model'));
        self::assertNotSame($key, (new RenderRequest($this->image(), 'Белый', '2K'))->key('provider', 'model'));
        self::assertNotSame($key, (new RenderRequest(new Image($this->image()->bytes.'extra'), 'Белый'))->key('provider', 'model'));
    }

    public static function invalidGenerations(): iterable
    {
        yield ['', 'pending', null];
        yield ['../job', 'pending', null];
        yield ['job', 'unknown', null];
        yield ['job', 'completed', null];
        yield ['job', 'processing', -1];
    }

    #[DataProvider('invalidGenerations')]
    public function testInvalidGeneration(string $id, string $status, ?int $cost): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Generation($id, $status, costKopecks: $cost);
    }

    public static function disabledConfigurations(): iterable
    {
        yield [false, 'nano_banana', 1000, 2000, 'disabled'];
        yield [true, 'unknown', 1000, 2000, 'Unknown'];
        yield [true, 'nano_banana', 0, 2000, 'Invalid'];
        yield [true, 'nano_banana', 1000, 500, 'Invalid'];
    }

    #[DataProvider('disabledConfigurations')]
    public function testNoPaidCallForInvalidConfiguration(bool $enabled, string $provider, int $max, int $budget, string $message): void
    {
        $generator = $this->generator();
        $generator->expects(self::never())->method('start');
        $this->expectExceptionMessage($message);
        (new RenderCabinet([$generator], $this->journal(), $enabled, $provider, $max, $budget))->advance(new RenderRequest($this->image(), 'Белый'));
    }

    public function testMissingCredentialsDoNotReserveBudget(): void
    {
        $generator = $this->generator();
        $generator->method('assertConfigured')->willThrowException(new \RuntimeException('missing key'));
        $generator->expects(self::never())->method('start');
        $journal = $this->journal();
        try {
            (new RenderCabinet([$generator], $journal, true, 'nano_banana', 1000, 2000))->advance(new RenderRequest($this->image(), 'Белый'));
            self::fail('Expected config failure');
        } catch (\RuntimeException $e) {
            self::assertSame('missing key', $e->getMessage());
        }
        self::assertSame([], $journal->records());
    }

    public function testBudgetReservationsBlockFurtherSubmissionsEvenAfterFailure(): void
    {
        $generator = $this->generator();
        $generator->expects(self::once())->method('start')->willReturn(new Generation('job', 'failed', costKopecks: 1500));
        $useCase = new RenderCabinet([$generator], $this->journal(), true, 'nano_banana', 1000, 2000);
        self::assertSame(1500, $useCase->advance(new RenderRequest($this->image(), 'Белый'))['reservedKopecks']);
        $this->expectExceptionMessage('budget exhausted');
        $useCase->advance(new RenderRequest($this->image(), 'Дуб'));
    }

    public static function terminalStates(): iterable
    {
        yield ['failed'];
        yield ['cancelled'];
    }

    #[DataProvider('terminalStates')]
    public function testTerminalJobsAreNotAutomaticallyResubmitted(string $state): void
    {
        $generator = $this->generator();
        $generator->expects(self::once())->method('start')->willReturn(new Generation('job', $state));
        $useCase = new RenderCabinet([$generator], $this->journal(), true, 'nano_banana', 1000, 2000);
        $request = new RenderRequest($this->image(), 'Белый');
        self::assertSame($state, $useCase->advance($request)['status']);
        $this->expectExceptionMessage($state);
        $useCase->advance($request);
    }

    public function testPollFailureCanBeResumedWithoutAnotherSubmission(): void
    {
        $generator = $this->generator();
        $generator->expects(self::once())->method('start')->willReturn(new Generation('job', 'pending'));
        $polls = 0;
        $generator->expects(self::exactly(2))->method('poll')->willReturnCallback(function () use (&$polls): Generation {
            if (++$polls === 1) {
                throw new \RuntimeException('download failed');
            }
            return new Generation('job', 'completed', $this->image());
        });
        $useCase = new RenderCabinet([$generator], $this->journal(), true, 'nano_banana', 1000, 2000);
        $request = new RenderRequest($this->image(), 'Белый');
        $useCase->advance($request);
        try {
            $useCase->advance($request);
            self::fail('Expected poll failure');
        } catch (\RuntimeException $e) {
            self::assertSame('download failed', $e->getMessage());
        }
        self::assertSame('completed', $useCase->advance($request)['status']);
    }

    public function testJournalRoundTripAndMissingArtifact(): void
    {
        $journal = $this->journal();
        $key = str_repeat('a', 64);
        $journal->exclusive(function () use ($journal, $key): void {
            self::assertSame([], $journal->records());
            $journal->save($key, ['status' => 'pending']);
            $journal->saveImage($key, $this->image());
        });
        self::assertSame('pending', $journal->records()[$key]['status']);
        self::assertSame($this->image()->bytes, $journal->image($key)->bytes);
        $this->expectExceptionMessage('Cannot read');
        $journal->image(str_repeat('b', 64));
    }

    public function testJournalRejectsTraversal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->journal()->image('../secret');
    }

    public function testInvalidJournalDoesNotResetBudget(): void
    {
        $root = sys_get_temp_dir().'/render-'.bin2hex(random_bytes(8));
        $journal = new LocalRenderJournal($root);
        $journal->exclusive(static fn () => file_put_contents($root.'/journal.json', 'null'));
        $this->expectExceptionMessage('Invalid AI journal');
        $journal->records();
    }

    private function generator(): \PHPUnit\Framework\MockObject\MockObject&ImageGenerator
    {
        $generator = $this->createMock(ImageGenerator::class);
        $generator->method('name')->willReturn('nano_banana');
        $generator->method('model')->willReturn('test-model');
        return $generator;
    }

    private function journal(): LocalRenderJournal
    {
        return new LocalRenderJournal(sys_get_temp_dir().'/render-'.bin2hex(random_bytes(8)));
    }

    private function image(): Image
    {
        return new Image(base64_decode(RenderingTest::PNG));
    }
}
