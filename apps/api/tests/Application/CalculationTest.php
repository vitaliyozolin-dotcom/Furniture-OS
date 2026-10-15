<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Calculation\Application\CalculatePrototypeCabinet;
use App\Calculation\Domain\CabinetInput;
use App\Calculation\Domain\PrototypeGeometry;
use App\Calculation\Domain\PrototypePricing;
use PHPUnit\Framework\TestCase;

final class CalculationTest extends TestCase
{
    public function testEveryCharacterizationFixture(): void
    {
        $fixtures = json_decode(file_get_contents(dirname(__DIR__, 4).'/contracts/fixtures/prototype-v0.json'), true, 512, JSON_THROW_ON_ERROR);
        $useCase = new CalculatePrototypeCabinet(new PrototypeGeometry(), new PrototypePricing());
        foreach ($fixtures as $fixture) {
            $actual = $useCase->calculate($fixture['input']);
            $costs = $actual['costs'];
            unset($actual['costs']);
            $expected = $fixture['expected'];
            $expectedCosts = $expected['costs'];
            unset($expected['costs']);
            self::assertEquals($expected, $actual, $fixture['id']);
            foreach ($expectedCosts as $name => $value) {
                self::assertEqualsWithDelta($value, (float) $costs[$name], 0.000000001, $fixture['id'].'.'.$name);
            }
        }
    }
    public function testRejectsEmptySectionsBeforeDivision(): void
    {
        $input = json_decode(file_get_contents(dirname(__DIR__, 4).'/contracts/fixtures/prototype-v0.json'), true)[0]['input'];
        $input['sections'] = [];
        $this->expectException(\InvalidArgumentException::class);
        CabinetInput::fromArray($input);
    }
}
