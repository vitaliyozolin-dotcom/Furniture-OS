<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence;

use App\Identity\Domain\Account;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'pilot_account')]
final class AccountRecord
{
    #[ORM\Id, ORM\Column(length: 32)] public string $id;
    #[ORM\Column(length: 254, unique: true)] public string $email;
    #[ORM\Column(length: 240)] public string $name;
    #[ORM\Column(length: 255)] public string $passwordHash;
    public function __construct(Account $account)
    {
        $this->id = $account->id;
        $this->email = $account->email;
        $this->name = $account->name;
        $this->passwordHash = $account->passwordHash;
    }
    public function account(): Account
    {
        return new Account($this->id, $this->email, $this->name, $this->passwordHash);
    }
}
