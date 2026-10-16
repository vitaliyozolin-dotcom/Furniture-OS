<?php

declare(strict_types=1);

namespace App\Marketplace\Domain;

final readonly class CabinetPublication
{
    public function __construct(public string $projectId, public string $revisionId, public string $token, public string $city, public string $description, public bool $active, public \DateTimeImmutable $publishedAt)
    {
        if (!preg_match('/^.{2,100}$/usD', trim($city)) || !preg_match('/^.{0,2000}$/usD', $description)) {
            throw new \InvalidArgumentException('Укажите город (2–100 символов) и описание до 2000 символов.');
        }
    }
}
