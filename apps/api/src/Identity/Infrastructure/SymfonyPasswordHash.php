<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure;

use App\Identity\Application\PasswordHash;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final readonly class SymfonyPasswordHash implements PasswordHash
{
    public function __construct(private PasswordHasherFactoryInterface $factory)
    {
    }
    public function hash(#[\SensitiveParameter] string $password): string
    {
        return $this->factory->getPasswordHasher(SecurityAccount::class)->hash($password);
    }
}
