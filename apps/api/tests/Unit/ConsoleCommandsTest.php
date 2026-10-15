<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Platform\Application\Readiness;
use App\Platform\Presentation\ReadinessCommand;
use App\Storage\Application\FileMetadataRepository;
use App\Storage\Application\StorageProbe;
use App\Storage\Domain\FileMetadata;
use App\Storage\Domain\StorageKey;
use App\Storage\Infrastructure\InMemoryFileStorage;
use App\Storage\Presentation\ProbeCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ConsoleCommandsTest extends TestCase
{
    public static function readinessStates(): iterable
    {
        yield 'available' => [true, 0];
        yield 'unavailable' => [false, 1];
    }

    #[DataProvider('readinessStates')]
    public function testReadinessExitCodeReportsAvailability(bool $available, int $expected): void
    {
        $readiness = $this->createMock(Readiness::class);
        $readiness->expects(self::once())->method('isReady')->willReturn($available);

        self::assertSame($expected, (new CommandTester(new ReadinessCommand($readiness)))->execute([]));
    }

    public function testProbeWritesAndThenChecksWithoutWritingAgain(): void
    {
        $files = [];
        $metadata = $this->createMock(FileMetadataRepository::class);
        $metadata->expects(self::once())->method('save')->willReturnCallback(static function (FileMetadata $file) use (&$files): void {
            $files[] = $file;
        });
        $metadata->expects(self::exactly(2))->method('all')->willReturnCallback(static function () use (&$files): array {
            return $files;
        });
        $storage = new InMemoryFileStorage();
        $command = new CommandTester(new ProbeCommand(new StorageProbe($storage, $metadata)));

        self::assertSame(0, $command->execute(['--write' => 'cli-probe']));
        self::assertSame(['status' => 'ok', 'failures' => []], json_decode($command->getDisplay(), true, flags: JSON_THROW_ON_ERROR));
        self::assertTrue($storage->exists(new StorageKey('cli-probe')));

        self::assertSame(0, $command->execute([]));
        self::assertSame(['status' => 'ok', 'failures' => []], json_decode($command->getDisplay(), true, flags: JSON_THROW_ON_ERROR));
    }

    public function testProbeReturnsFailureWhenAnObjectIsMissing(): void
    {
        $metadata = $this->createMock(FileMetadataRepository::class);
        $metadata->expects(self::never())->method('save');
        $metadata->expects(self::once())->method('all')->willReturn([
            new FileMetadata('missing', 'system', 'migration-probe', 'text/plain', 4, hash('sha256', 'file'), new StorageKey('missing'), new \DateTimeImmutable()),
        ]);
        $command = new CommandTester(new ProbeCommand(new StorageProbe(new InMemoryFileStorage(), $metadata)));

        self::assertSame(1, $command->execute([]));
        self::assertSame(['status' => 'failed', 'failures' => ['missing:missing']], json_decode($command->getDisplay(), true, flags: JSON_THROW_ON_ERROR));
    }
}
