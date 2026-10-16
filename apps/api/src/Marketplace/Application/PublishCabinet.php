<?php

declare(strict_types=1);

namespace App\Marketplace\Application;

use App\Marketplace\Domain\CabinetPublication;
use App\Marketplace\Domain\Publications;
use App\Project\Application\Contract\ProjectRevisions;

final readonly class PublishCabinet
{
    public function __construct(private Publications $publications, private ProjectRevisions $revisions)
    {
    }
    public function publish(string $projectId, int $version, string $city, string $description): array
    {
        $revision = $this->revisions->issue($projectId, $version);
        $existing = $this->publications->forProject($projectId);
        if ($existing !== null && $existing->active && $existing->revisionId === $revision['id'] && $existing->city === trim($city) && $existing->description === $description) {
            return $this->view($existing);
        }
        $publication = new CabinetPublication($projectId, $revision['id'], bin2hex(random_bytes(16)), trim($city), $description, true, new \DateTimeImmutable());
        $this->publications->save($publication);
        return $this->view($publication);
    }
    public function publicView(string $token): array
    {
        $publication = $this->publications->byToken($token);
        if ($publication === null || !$publication->active) {
            throw new \OutOfBoundsException('Заявка не найдена или снята с публикации.');
        }
        return $this->view($publication);
    }
    public function withdraw(string $projectId): void
    {
        $this->revisions->requireOwnership($projectId);
        $publication = $this->publications->forProject($projectId);
        if ($publication !== null && $publication->active) {
            $this->publications->save(new CabinetPublication($publication->projectId, $publication->revisionId, $publication->token, $publication->city, $publication->description, false, $publication->publishedAt));
        }
    }
    private function view(CabinetPublication $publication): array
    {
        $revision = $this->revisions->read($publication->revisionId);
        return ['token' => $publication->token, 'title' => $revision['title'], 'city' => $publication->city, 'description' => $publication->description, 'configuration' => $revision['configuration'], 'estimate' => $revision['estimate'], 'rulesetVersion' => $revision['rulesetVersion'], 'publishedAt' => $publication->publishedAt->format(DATE_ATOM)];
    }
}
