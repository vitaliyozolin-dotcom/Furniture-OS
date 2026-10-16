<?php

declare(strict_types=1);

namespace App\Cabinet\Domain;

final readonly class CabinetConfiguration
{
    private function __construct(public array $values)
    {
    }
    public static function fromArray(array $values): self
    {
        $keys = ['width', 'height', 'depth', 'sections', 'bodyMaterial', 'facadeMaterial'];
        if (array_diff($keys, array_keys($values)) || array_diff(array_keys($values), $keys)) {
            throw new \InvalidArgumentException('Конфигурация шкафа заполнена не полностью.');
        }
        foreach (['width' => [300, 5000], 'height' => [400, 3000], 'depth' => [200, 1000]] as $field => [$min, $max]) {
            if (!is_int($values[$field]) || $values[$field] < $min || $values[$field] > $max) {
                throw new \InvalidArgumentException('Габариты должны быть целыми миллиметрами в пределах конструктора.');
            }
        }
        if (!in_array($values['bodyMaterial'], ['sonoma', 'cashmere', 'graphite'], true) || !in_array($values['facadeMaterial'], ['chipboard', 'mdf', 'mirror'], true)) {
            throw new \InvalidArgumentException('Выберите материал из доступных вариантов.');
        }
        if (!is_array($values['sections']) || !array_is_list($values['sections']) || count($values['sections']) < 1 || count($values['sections']) > 5) {
            throw new \InvalidArgumentException('Допускается от 1 до 5 равных секций.');
        }
        foreach ($values['sections'] as $section) {
            if (!is_array($section) || count($section) !== 3 || !isset($section['type'], $section['shelves'], $section['drawers']) || !in_array($section['type'], ['shelves', 'wardrobe', 'drawers'], true)) {
                throw new \InvalidArgumentException('Проверьте наполнение секции.');
            }
            foreach (['shelves' => 12, 'drawers' => 6] as $field => $max) {
                if (!is_int($section[$field]) || $section[$field] < 0 || $section[$field] > $max) {
                    throw new \InvalidArgumentException('Недопустимое количество полок или ящиков.');
                }
            }
        }
        return new self($values);
    }
}
