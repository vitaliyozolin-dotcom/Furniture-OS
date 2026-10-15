<?php

declare(strict_types=1);

namespace App\Rendering\Application;

final readonly class RenderRequest
{
    public const PROMPT_VERSION = 'cabinet-concept-v1';

    public function __construct(public Image $reference, public string $instructions, public string $resolution = '1K')
    {
        if (trim($instructions) === '' || strlen($instructions) > 12000 || !in_array($resolution, ['1K', '2K', '4K'], true)) {
            throw new \InvalidArgumentException('Provide instructions (up to 12000 bytes) and a resolution of 1K, 2K or 4K.');
        }
    }

    public function prompt(): string
    {
        return "Create a conceptual furniture visualization from the reference. Preserve the cabinet outline, proportions, number and positions of sections, doors, shelves and drawers. Do not add or remove structural elements. Change only materials, lighting and surroundings as requested. Do not add dimensions or text. User instructions: ".$this->instructions;
    }

    public function key(string $provider, string $model): string
    {
        return hash('sha256', json_encode([$provider, $model, self::PROMPT_VERSION, hash('sha256', $this->reference->bytes), $this->instructions, $this->resolution], JSON_THROW_ON_ERROR));
    }
}
