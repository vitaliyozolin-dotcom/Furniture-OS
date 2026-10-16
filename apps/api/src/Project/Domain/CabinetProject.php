<?php

declare(strict_types=1);

namespace App\Project\Domain;

final readonly class CabinetProject
{
    public function __construct(public string $id, public string $ownerId, public string $title, public array $configuration, public int $version, public \DateTimeImmutable $updatedAt)
    {
        if (!preg_match('/^.{2,100}$/usD', trim($title))) {
            throw new \InvalidArgumentException('Название проекта: 2–100 символов.');
        }
    }
    public function requireVersion(int $expected): void
    {
        if ($expected !== $this->version) {
            throw new \DomainException('Проект уже изменён. Откройте актуальную версию перед сохранением.');
        }
    }
    public function view(): array
    {
        return ['id' => $this->id, 'title' => $this->title, 'configuration' => $this->configuration, 'version' => $this->version, 'updatedAt' => $this->updatedAt->format(DATE_ATOM)];
    }
}
