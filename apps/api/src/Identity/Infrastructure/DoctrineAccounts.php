<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure;

use App\Identity\Domain\Account;
use App\Identity\Domain\Accounts;
use App\Identity\Infrastructure\Persistence\AccountRecord;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final readonly class DoctrineAccounts implements Accounts
{
    public function __construct(private EntityManagerInterface $em)
    {
    }
    public function byEmail(string $email): ?Account
    {
        return $this->em->getRepository(AccountRecord::class)->findOneBy(['email' => $email])?->account();
    }
    public function add(Account $account): void
    {
        try {
            $this->em->persist(new AccountRecord($account));
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            throw new \DomainException('Этот email уже зарегистрирован.');
        }
    }
}
