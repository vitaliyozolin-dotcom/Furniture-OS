<?php

declare(strict_types=1);

namespace App\Tests\Rendering;

use App\Rendering\Application\Image;
use App\Rendering\Application\RenderCabinet;
use App\Rendering\Application\RenderRequest;
use App\Rendering\Infrastructure\PolzaNanoBanana;
use App\Rendering\Presentation\RenderCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class RenderingContainerTest extends KernelTestCase
{
    public function testContainerWiresDisabledCliAndNanoBananaProvider(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $provider = $container->get(PolzaNanoBanana::class);
        self::assertSame('nano_banana', $provider->name());
        self::assertSame('google/gemini-nano-banana-2.1', $provider->model());
        $command = $container->get(RenderCommand::class);
        self::assertSame('app:ai:render', $command->getName());
        $tester = new CommandTester($command);
        self::assertSame(1, $tester->execute(['reference' => '/missing.png', 'instructions' => 'Дуб']));
        $render = $container->get(RenderCabinet::class);
        $this->expectExceptionMessage('AI is disabled');
        $render->advance(new RenderRequest(new Image(base64_decode(RenderingTest::PNG)), 'Дуб'));
    }
}
