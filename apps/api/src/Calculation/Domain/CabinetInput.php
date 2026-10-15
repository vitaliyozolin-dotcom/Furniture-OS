<?php

declare(strict_types=1);

namespace App\Calculation\Domain;

final readonly class CabinetInput
{
    private function __construct(public array $values)
    {
    }
    public static function fromArray(array $input): self
    {
        $fields = ['width', 'height', 'depth', 'sections', 'bodyMaterial', 'facadeMaterial', 'marginPercent', 'laborRate'];
        if (array_diff($fields, array_keys($input)) || array_diff(array_keys($input), $fields)) {
            throw new \InvalidArgumentException('Input must match PrototypeCabinetInputV0.');
        }
        foreach (['width', 'height', 'depth', 'marginPercent', 'laborRate'] as $field) {
            self::number($input[$field]);
        }
        if (!in_array($input['bodyMaterial'], ['sonoma', 'cashmere', 'graphite'], true) || !in_array($input['facadeMaterial'], ['chipboard', 'mdf', 'mirror'], true)) {
            throw new \InvalidArgumentException('Unknown material.');
        }
        if (!is_array($input['sections']) || !array_is_list($input['sections']) || count($input['sections']) < 1 || count($input['sections']) > 100) {
            throw new \InvalidArgumentException('Expected 1–100 sections for a finite compatibility calculation.');
        }
        foreach ($input['sections'] as $section) {
            if (!is_array($section) || count($section) !== 3 || !isset($section['type'], $section['shelves'], $section['drawers']) || !in_array($section['type'], ['shelves', 'wardrobe', 'drawers'], true)) {
                throw new \InvalidArgumentException('Invalid section.');
            }
            self::number($section['shelves']);
            self::number($section['drawers']);
        }
        return new self($input);
    }
    private static function number(mixed $value): void
    {
        if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value) || abs($value) > 1000000) {
            throw new \InvalidArgumentException('Expected a finite number within transport safety limits.');
        }
    }
}
