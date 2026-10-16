<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure;

use App\Identity\Domain\Accounts;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

/** @implements UserProviderInterface<SecurityAccount> */
final readonly class AccountProvider implements UserProviderInterface
{
    public function __construct(private Accounts $accounts)
    {
    }
    public function loadUserByIdentifier(string $identifier): SecurityAccount
    {
        $account = $this->accounts->byEmail(strtolower(trim($identifier)));
        if ($account === null) {
            throw new UserNotFoundException();
        }
        return new SecurityAccount($account);
    }
    public function refreshUser(UserInterface $user): SecurityAccount
    {
        if (!$user instanceof SecurityAccount) {
            throw new UnsupportedUserException();
        }
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }
    public function supportsClass(string $class): bool
    {
        return $class === SecurityAccount::class;
    }
}
