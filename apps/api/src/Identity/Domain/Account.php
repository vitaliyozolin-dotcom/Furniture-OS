<?php

declare(strict_types=1);

namespace App\Identity\Domain;

final readonly class Account
{
    public function __construct(public string $id, public string $email, public string $name, public string $passwordHash)
    {
    }
    public static function normalizeEmail(string $email): string
    {
        $email = strtolower(trim($email));
        if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Укажите корректный email.');
        }
        return $email;
    }
    public static function validateRegistration(string $name, string $email, #[\SensitiveParameter] string $password): void
    {
        self::normalizeEmail($email);
        if (!preg_match('/^.{2,80}$/usD', trim($name)) || strlen($password) < 10 || strlen($password) > 128) {
            throw new \InvalidArgumentException('Имя: 2–80 символов. Пароль: 10–128 байт.');
        }
    }
    public function profile(): array
    {
        return ['id' => $this->id, 'email' => $this->email, 'name' => $this->name];
    }
}
