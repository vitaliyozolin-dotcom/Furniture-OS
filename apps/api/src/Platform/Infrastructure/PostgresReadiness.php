<?php

declare(strict_types=1);

namespace App\Platform\Infrastructure;

use App\Platform\Application\Readiness;
use Doctrine\DBAL\Connection;

final readonly class PostgresReadiness implements Readiness
{
    public function __construct(private Connection $connection)
    {
    }
    public function isReady(): bool
    {
        try {
            return $this->connection->fetchOne('SELECT 1') === 1;
        } catch (\Throwable) {
            return false;
        }
    }
}
