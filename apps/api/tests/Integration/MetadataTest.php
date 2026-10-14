<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Storage\Application\FileMetadataRepository;
use App\Storage\Application\StorageProbe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MetadataTest extends KernelTestCase
{
    public function testPersistsAndReloadsTechnicalMetadata(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $id = 'test-'.bin2hex(random_bytes(8));
        $probe = $container->get(StorageProbe::class);
        $probe->write($id);
        $probe->write($id);
        $container->get(EntityManagerInterface::class)->clear();
        $file = $container->get(FileMetadataRepository::class)->find($id);
        self::assertNotNull($file);
        self::assertSame('system', $file->owner);
        self::assertSame('text/plain', $file->mime);
        self::assertSame([], $probe->verify());
        self::assertNull($container->get(FileMetadataRepository::class)->find('absent'));
    }
}
