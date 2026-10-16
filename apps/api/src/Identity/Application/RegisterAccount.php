<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Domain\Account;
use App\Identity\Domain\Accounts;

final readonly class RegisterAccount
{
    public function __construct(private Accounts $accounts, private PasswordHash $passwords)
    {
    }
    public function register(string $name, string $email, #[\SensitiveParameter] string $password): array
    {
        Account::validateRegistration($name, $email, $password);
        $email = Account::normalizeEmail($email);
        if ($this->accounts->byEmail($email) !== null) {
            throw new \DomainException('Этот email уже зарегистрирован.');
        }
        $account = new Account(bin2hex(random_bytes(16)), $email, trim($name), $this->passwords->hash($password));
        $this->accounts->add($account);
        return $account->profile();
    }
}
