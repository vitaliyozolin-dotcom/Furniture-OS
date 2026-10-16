<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Identity\Domain\Account;
use App\Identity\Domain\Accounts;
use App\Identity\Infrastructure\AccountProvider;
use App\Identity\Infrastructure\SecurityAccount;
use App\Project\Domain\CabinetProject;
use App\Project\Domain\Projects;
use App\Project\Application\Contract\ProjectRevisions;
use App\Tests\Unit\PilotRulesTest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

final class PilotPersistenceTest extends KernelTestCase
{
    public function testConcurrentUpdateIsRejectedWithoutOverwriting(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $projects = $container->get(Projects::class);
        $project = $projects->save(new CabinetProject(bin2hex(random_bytes(16)), 'owner', 'Версия', PilotRulesTest::configuration(), 1, new \DateTimeImmutable()));
        // Simulate another committed transaction after this entity manager read version 1.
        $container->get(EntityManagerInterface::class)->getConnection()->executeStatement('UPDATE cabinet_project SET version = version + 1 WHERE id = ?', [$project->id]);
        $this->expectException(\DomainException::class);
        $projects->save(new CabinetProject($project->id, 'owner', 'Устаревшая запись', $project->configuration, 1, new \DateTimeImmutable()));
    }
    public function testUniqueEmailRaceIsMappedToConflict(): void
    {
        self::bootKernel();
        $accounts = self::getContainer()->get(Accounts::class);
        $email = bin2hex(random_bytes(8)).'@example.test';
        $accounts->add(new Account(bin2hex(random_bytes(16)), $email, 'Первый', 'hash'));
        $this->expectException(\DomainException::class);
        $accounts->add(new Account(bin2hex(random_bytes(16)), $email, 'Второй', 'hash'));
    }
    public function testMissingRevisionDoesNotExposeAnything(): void
    {
        self::bootKernel();
        $this->expectException(\OutOfBoundsException::class);
        self::getContainer()->get(ProjectRevisions::class)->read(str_repeat('0', 32));
    }
    public function testUserProviderOnlySupportsItsOwnUserType(): void
    {
        self::bootKernel();
        $provider = self::getContainer()->get(AccountProvider::class);
        self::assertTrue($provider->supportsClass(SecurityAccount::class));
        self::assertFalse($provider->supportsClass(UserInterface::class));
        $this->expectException(UnsupportedUserException::class);
        $provider->refreshUser($this->createMock(UserInterface::class));
    }
}
