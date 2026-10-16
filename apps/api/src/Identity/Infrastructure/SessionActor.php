<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure;

use App\Identity\Application\Contract\CurrentActor;
use App\Shared\Application\Contract\AuthenticationRequired;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class SessionActor implements CurrentActor
{
    public function __construct(private Security $security)
    {
    }
    public function profile(): ?array
    {
        $user = $this->security->getUser();
        return $user instanceof SecurityAccount ? $user->account->profile() : null;
    }
    public function requireId(): string
    {
        $user = $this->security->getUser();
        if (!$user instanceof SecurityAccount) {
            throw new AuthenticationRequired('Войдите в аккаунт.');
        }
        return $user->account->id;
    }
}
