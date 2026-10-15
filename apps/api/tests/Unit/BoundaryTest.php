<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Calculation\Application\CalculatePrototypeCabinet;
use App\Calculation\Domain\CabinetInput;
use App\Calculation\Domain\PrototypeGeometry;
use App\Calculation\Domain\PrototypePricing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BoundaryTest extends TestCase
{
    public static function invalidInputs(): iterable
    {
        yield [['width' => '2400']];
        yield [['height' => INF]];
        yield [['depth' => 1000001]];
        yield [['bodyMaterial' => 'unknown']];
        yield [['facadeMaterial' => 'unknown']];
        yield [['sections' => 'wrong']];
        yield [['sections' => [['type' => 'unknown', 'shelves' => 1, 'drawers' => 0]]]];
        yield [['sections' => [['type' => 'shelves', 'shelves' => null, 'drawers' => 0]]]];
        yield [['sections' => [['type' => 'shelves', 'shelves' => '1', 'drawers' => 0]]]];
        yield [['extra' => true]];
    }
    #[DataProvider('invalidInputs')]
    public function testTransportRejectsUncomputableInput(array $changes): void
    {
        $input = self::fixture();
        $this->expectException(\InvalidArgumentException::class);
        CabinetInput::fromArray(array_replace($input, $changes));
    }
    public function testSingleSectionMirrorAndWidthWarning(): void
    {
        $input = self::fixture();
        $input['sections'] = [['type' => 'shelves', 'shelves' => 0, 'drawers' => 0]];
        $input['facadeMaterial'] = 'mirror';
        $input['width'] = 900;
        $useCase = new CalculatePrototypeCabinet(new PrototypeGeometry(), new PrototypePricing());
        $result = $useCase->calculate($input);
        self::assertSame(['Полки шириной 868 мм требуют усиления.'], $result['warnings']);
        self::assertSame(9, $result['leadDays']);
        self::assertCount(5, $result['parts']);
    }
    private static function fixture(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__, 4).'/contracts/fixtures/prototype-v0.json'), true)[0]['input'];
    }
}
