<?php

declare(strict_types=1);

namespace App\Identity\Domain;

interface Accounts
{
    public function byEmail(string $email): ?Account;
    public function add(Account $account): void;
}
