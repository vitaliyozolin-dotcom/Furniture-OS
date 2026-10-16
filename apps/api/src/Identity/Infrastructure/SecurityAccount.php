<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure;

use App\Identity\Domain\Account;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

final readonly class SecurityAccount implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(public Account $account)
    {
    }
    public function getUserIdentifier(): string
    {
        return $this->account->email;
    }
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }
    public function getPassword(): string
    {
        return $this->account->passwordHash;
    }
    public function eraseCredentials(): void
    {
    }
}
