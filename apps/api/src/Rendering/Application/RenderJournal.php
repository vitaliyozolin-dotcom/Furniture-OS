<?php

declare(strict_types=1);

namespace App\Rendering\Application;

interface RenderJournal
{
    /** Serializes reservations, paid submissions and updates across CLI processes. */
    public function exclusive(callable $operation): mixed;
    public function records(): array;
    public function save(string $key, array $record): void;
    public function saveImage(string $key, Image $image): void;
    public function image(string $key): Image;
}
