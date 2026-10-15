<?php

declare(strict_types=1);

namespace App\Rendering\Application;

interface ImageGenerator
{
    public function name(): string;
    public function model(): string;
    public function assertConfigured(): void;
    public function start(RenderRequest $request, int $maxCostKopecks): Generation;
    public function poll(string $id): Generation;
}
