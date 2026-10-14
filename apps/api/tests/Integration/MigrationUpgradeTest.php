<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Process\Process;

final class MigrationUpgradeTest extends KernelTestCase
{
    public function testEmptyDatabaseAndPopulatedPreviousSchemaUpgrade(): void
    {
        self::bootKernel();
        $connection = self::getContainer()->get(Connection::class);
        $params = $connection->getParams();
        $base = 'migration_check_'.bin2hex(random_bytes(6));
        $name = $base.'_test';
        $connection->executeStatement('CREATE DATABASE '.$name);
        $url = 'postgresql://'.rawurlencode($params['user']).':'.rawurlencode($params['password']).'@'.$params['host'].':'.($params['port'] ?? 5432).'/'.$base.'?charset=utf8';
        $run = static function (array $arguments) use ($url, $name): string {
            $process = new Process([PHP_BINARY, 'bin/console', ...$arguments], dirname(__DIR__, 2), ['DATABASE_URL' => $url, 'APP_ENV' => 'test', 'STORAGE_ROOT' => sys_get_temp_dir().'/'.$name]);
            $process->mustRun();
            return $process->getOutput().$process->getErrorOutput();
        };
        try {
            $run(['doctrine:migrations:migrate', 'DoctrineMigrations\\Version20261015000100', '--no-interaction']);
            self::assertStringContainsString('ok', $run(['app:storage:probe', '--write=upgrade-preserved']));
            $run(['doctrine:migrations:migrate', '--no-interaction']);
            self::assertStringContainsString('in sync', $run(['doctrine:schema:validate']));
            self::assertStringContainsString('ok', $run(['app:storage:probe']));
            // Ensure the metadata row, not just an empty checksum scan, survived.
            $verify = new Process([PHP_BINARY, 'bin/console', 'dbal:run-sql', "SELECT id FROM stored_file WHERE id = 'upgrade-preserved'"], dirname(__DIR__, 2), ['DATABASE_URL' => $url, 'APP_ENV' => 'test', 'STORAGE_ROOT' => sys_get_temp_dir().'/'.$name]);
            $verify->mustRun();
            self::assertStringContainsString('upgrade-preserved', $verify->getOutput());
        } finally {
            $connection->executeStatement('DROP DATABASE '.$name);
        }
    }
}
