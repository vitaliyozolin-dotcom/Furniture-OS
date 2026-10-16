<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Cabinet\Domain\CabinetConfiguration;
use App\Identity\Domain\Account;
use App\Marketplace\Domain\CabinetPublication;
use App\Project\Domain\CabinetProject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PilotRulesTest extends TestCase
{
    public static function configuration(): array
    {
        return ['width' => 2400, 'height' => 2200, 'depth' => 600, 'sections' => [['type' => 'wardrobe', 'shelves' => 1, 'drawers' => 2]], 'bodyMaterial' => 'sonoma', 'facadeMaterial' => 'chipboard'];
    }
    public static function invalidConfigurations(): iterable
    {
        $valid = self::configuration();
        yield 'missing' => [[]];
        yield 'extra' => [[...$valid, 'marginPercent' => 1]];
        foreach (['width' => [299, 5001, 350.5, '1000'], 'height' => [399, 3001, 500.1], 'depth' => [199, 1001, null]] as $field => $values) {
            foreach ($values as $value) {
                yield $field.'-'.var_export($value, true) => [array_replace($valid, [$field => $value])];
            }
        }
        yield 'body material' => [array_replace($valid, ['bodyMaterial' => 'unknown'])];
        yield 'facade material' => [array_replace($valid, ['facadeMaterial' => 'unknown'])];
        foreach ([null, [], ['key' => $valid['sections'][0]], array_fill(0, 6, $valid['sections'][0]), [null], [[]], [['type' => 'unknown', 'shelves' => 0, 'drawers' => 0]], [['type' => 'shelves', 'shelves' => -1, 'drawers' => 0]], [['type' => 'shelves', 'shelves' => 13, 'drawers' => 0]], [['type' => 'shelves', 'shelves' => '1', 'drawers' => 0]], [['type' => 'drawers', 'shelves' => 0, 'drawers' => 7]], [['type' => 'drawers', 'shelves' => 0, 'drawers' => -1]], [['type' => 'drawers', 'shelves' => 0, 'drawers' => 0.5]]] as $index => $sections) {
            yield 'sections-'.$index => [array_replace($valid, ['sections' => $sections])];
        }
    }
    #[DataProvider('invalidConfigurations')]
    public function testInvalidConfiguration(array $configuration): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CabinetConfiguration::fromArray($configuration);
    }
    public function testValidBoundaryConfigurations(): void
    {
        foreach (['sonoma', 'cashmere', 'graphite'] as $material) {
            $config = [...self::configuration(), 'bodyMaterial' => $material];
            self::assertSame($config, CabinetConfiguration::fromArray($config)->values);
        }
        foreach (['chipboard', 'mdf', 'mirror'] as $material) {
            $config = [...self::configuration(), 'facadeMaterial' => $material];
            self::assertSame($config, CabinetConfiguration::fromArray($config)->values);
        }
    }
    public static function invalidAccounts(): iterable
    {
        yield ['a', 'test@example.com', 'Password123'];
        yield ['Имя', 'invalid', 'Password123'];
        yield ['Имя', str_repeat('a', 255).'@x.com', 'Password123'];
        yield ['Имя', 'test@example.com', 'short'];
        yield ['Имя', 'test@example.com', str_repeat('a', 129)];
        yield [str_repeat('я', 81), 'test@example.com', 'Password123'];
    }
    #[DataProvider('invalidAccounts')]
    public function testInvalidRegistration(string $name, string $email, string $password): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Account::validateRegistration($name, $email, $password);
    }
    public function testVersionAndTitleInvariants(): void
    {
        $project = new CabinetProject('id', 'owner', 'Шкаф', self::configuration(), 3, new \DateTimeImmutable());
        $project->requireVersion(3);
        self::assertArrayNotHasKey('ownerId', $project->view());
        $this->expectException(\DomainException::class);
        $project->requireVersion(2);
    }
    public function testInvalidProjectTitle(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CabinetProject('id', 'owner', ' ', [], 1, new \DateTimeImmutable());
    }
    public static function invalidPublications(): iterable
    {
        yield ['x', ''];
        yield [str_repeat('x', 101), ''];
        yield ['Москва', str_repeat('x', 2001)];
    }
    #[DataProvider('invalidPublications')]
    public function testInvalidPublication(string $city, string $description): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CabinetPublication('p', 'r', 't', $city, $description, true, new \DateTimeImmutable());
    }
}
