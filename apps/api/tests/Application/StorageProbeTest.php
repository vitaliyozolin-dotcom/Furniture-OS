<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Storage\Application\FileMetadataRepository;
use App\Storage\Application\StorageProbe;
use App\Storage\Domain\FileMetadata;
use App\Storage\Domain\StorageKey;
use App\Storage\Infrastructure\InMemoryFileStorage;
use PHPUnit\Framework\TestCase;

final class StorageProbeTest extends TestCase
{
    public function testReportsMissingAndCorruptObjects(): void
    {
        $files = [];
        foreach (['missing', 'corrupt'] as $id) {
            $files[] = new FileMetadata($id, 'system', 'test', 'text/plain', 4, hash('sha256', 'good'), new StorageKey($id), new \DateTimeImmutable());
        }
        $repository = $this->createMock(FileMetadataRepository::class);
        $repository->expects(self::once())->method('all')->willReturn($files);
        $storage = new InMemoryFileStorage();
        $storage->write(new StorageKey('corrupt'), 'bad', hash('sha256', 'bad'));
        $storage->write(new StorageKey('orphan'), 'extra', hash('sha256', 'extra'));
        self::assertSame(['missing:missing', 'corrupt:checksum', 'orphan:orphan'], (new StorageProbe($storage, $repository))->verify());
    }
    public function testMetadataRejectsOversizeAndUnapprovedMime(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FileMetadata('id', 'system', 'test', 'text/html', 10485761, 'wrong', new StorageKey('id'), new \DateTimeImmutable());
    }
}
