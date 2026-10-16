<?php

declare(strict_types=1);

namespace App\Marketplace\Domain;

interface Publications
{
    public function forProject(string $id): ?CabinetPublication;
    public function byToken(string $token): ?CabinetPublication;
    public function save(CabinetPublication $publication): void;
}
