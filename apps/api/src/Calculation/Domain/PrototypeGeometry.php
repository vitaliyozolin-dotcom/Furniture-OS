<?php

declare(strict_types=1);

namespace App\Calculation\Domain;

final class PrototypeGeometry
{
    public function calculate(CabinetInput $input): array
    {
        $c = $input->values;
        $count = count($c['sections']);
        $innerHeight = $c['height'] - 32;
        $sectionWidth = ($c['width'] - 32 - 16 * ($count - 1)) / $count;
        $parts = [];
        $add = static function (string $name, int|float $width, int|float $height, int|float $quantity, string $material = 'body') use (&$parts): void {
            $parts[] = compact('name', 'width', 'height', 'quantity', 'material');
        };
        $add('Боковина', $c['height'], $c['depth'], 2);
        $add('Крышка', $c['width'] - 32, $c['depth'], 1);
        $add('Дно', $c['width'] - 32, $c['depth'], 1);
        $add('Задняя стенка', $c['width'] - 32, $innerHeight, 1, 'back');
        if ($count > 1) {
            $add('Перегородка', $innerHeight, $c['depth'], $count - 1);
        }
        $runners = $rods = $shelfCount = 0;
        foreach ($c['sections'] as $index => $section) {
            $shelfCount += $section['shelves'];
            $runners += $section['drawers'];
            if (in_array($section['type'], ['wardrobe', 'drawers'], true)) {
                ++$rods;
            }
            if ($section['shelves'] > 0) {
                $add('Полка секции '.($index + 1), $sectionWidth - 2, $c['depth'] - 20, $section['shelves']);
            }
            if ($section['drawers'] > 0) {
                $add('Боковина ящика '.($index + 1), 450, 160, $section['drawers'] * 2);
                $add('Перед/зад ящика '.($index + 1), $sectionWidth - 70, 160, $section['drawers'] * 2);
                $add('Дно ящика '.($index + 1), $sectionWidth - 70, 450, $section['drawers'], 'back');
            }
        }
        $add('Фасад', ($c['width'] - 2 * ($count + 1)) / $count, $c['height'] - 4, $count, 'facade');
        $area = ['body' => 0.0, 'back' => 0.0, 'facade' => 0.0];
        foreach ($parts as $part) {
            $area[$part['material']] += $part['width'] * $part['height'] * $part['quantity'] / 1000000;
        }
        $sheets = (int) ceil($area['body'] / (2.8 * 2.07) * 1.12);
        $hinges = max(3, (int) ceil($c['height'] / 600)) * $count;
        $edgeMeters = floor(($area['body'] * 2.7 + $shelfCount * $sectionWidth * 2 / 1000) * 10 + 0.5) / 10;
        $warnings = [];
        $roundedWidth = (int) floor($sectionWidth + 0.5);
        if ($sectionWidth > 950) {
            $warnings[] = "Секция {$roundedWidth} мм слишком широкая — нужна перегородка.";
        } elseif ($sectionWidth > 800) {
            $warnings[] = "Полки шириной {$roundedWidth} мм требуют усиления.";
        }
        if ($c['depth'] < 500 && $rods > 0) {
            $warnings[] = 'Глубины недостаточно для стандартной продольной штанги.';
        }
        if ($c['height'] > 2600) {
            $warnings[] = 'Высота выше 2600 мм требует проверки транспорта и монтажа.';
        }
        $leadDays = max(5, (int) ceil(4 + $count + $runners * 0.7 + match ($c['facadeMaterial']) {
            'mdf' => 8, 'mirror' => 4, default => 0
        }));
        return compact('parts', 'sheets', 'hinges', 'runners', 'rods', 'edgeMeters', 'leadDays', 'warnings', 'area', 'shelfCount');
    }
}
