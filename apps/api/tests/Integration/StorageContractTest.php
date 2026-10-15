<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Storage\Application\FileStorage;
use App\Storage\Domain\StorageKey;
use App\Storage\Infrastructure\InMemoryFileStorage;
use App\Storage\Infrastructure\LocalFileStorage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StorageContractTest extends TestCase
{
    public static function adapters(): iterable
    {
        yield 'memory' => [false];
        yield 'local' => [true];
    }
    private function adapter(bool $local): FileStorage
    {
        return $local ? new LocalFileStorage(sys_get_temp_dir().'/furniture-test-'.bin2hex(random_bytes(8))) : new InMemoryFileStorage();
    }
    #[DataProvider('adapters')]
    public function testRoundTripBinaryZeroByteIdempotencyAndDelete(bool $local): void
    {
        $storage = $this->adapter($local);
        foreach (["\x00\xff\x01binary", ''] as $index => $bytes) {
            $key = new StorageKey('contract-'.$index);
            self::assertFalse($storage->exists($key));
            $storage->write($key, $bytes, hash('sha256', $bytes));
            $storage->write($key, $bytes, hash('sha256', $bytes));
            self::assertTrue($storage->exists($key));
            self::assertCount(1, iterator_to_array($storage->keys()));
            self::assertSame($bytes, $storage->read($key));
            $storage->delete($key);
            $storage->delete($key);
            self::assertFalse($storage->exists($key));
        }
    }
    #[DataProvider('adapters')]
    public function testRejectsConflictingOverwrite(bool $local): void
    {
        $storage = $this->adapter($local);
        $key = new StorageKey('immutable');
        $storage->write($key, 'one', hash('sha256', 'one'));
        $this->expectException(\RuntimeException::class);
        $storage->write($key, 'two', hash('sha256', 'two'));
    }
    #[DataProvider('adapters')]
    public function testRejectsWrongChecksum(bool $local): void
    {
        $storage = $this->adapter($local);
        $this->expectException(\InvalidArgumentException::class);
        $storage->write(new StorageKey('bad'), 'bytes', hash('sha256', 'other'));
    }
    #[DataProvider('adapters')]
    public function testMissingObjectFails(bool $local): void
    {
        $storage = $this->adapter($local);
        $this->expectException(\Throwable::class);
        $storage->read(new StorageKey('missing'));
    }
    public static function badKeys(): iterable
    {
        foreach (['../escape', '/absolute', 'x/y', 'x\\y', '', '.lock', 'UPPER', "a\0b", 'https://bucket/file', 'tmp-reserved'] as $key) {
            yield [$key];
        }
    }
    #[DataProvider('badKeys')]
    public function testRejectsTraversal(string $key): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new StorageKey($key);
    }
}
