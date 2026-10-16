<?php

declare(strict_types=1);

namespace App\Marketplace\Infrastructure\Persistence;

use App\Marketplace\Domain\CabinetPublication;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\Table(name: 'cabinet_publication')]
final class PublicationRecord
{
    #[ORM\Id, ORM\Column(length: 32)] public string $projectId;
    #[ORM\Column(length: 32)] public string $revisionId;
    #[ORM\Column(length: 32, unique: true)] public string $token;
    #[ORM\Column(length: 300)] public string $city;
    #[ORM\Column(type: 'text')] public string $description;
    #[ORM\Column] public bool $active;
    #[ORM\Column(type: 'datetime_immutable')] public \DateTimeImmutable $publishedAt;
    public function apply(CabinetPublication $publication): void
    {
        foreach (['projectId', 'revisionId', 'token', 'city', 'description', 'active', 'publishedAt'] as $field) {
            $this->$field = $publication->$field;
        }
    }
    public function publication(): CabinetPublication
    {
        return new CabinetPublication($this->projectId, $this->revisionId, $this->token, $this->city, $this->description, $this->active, $this->publishedAt);
    }
}
