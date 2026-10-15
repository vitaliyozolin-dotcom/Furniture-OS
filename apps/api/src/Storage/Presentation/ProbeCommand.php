<?php

declare(strict_types=1);

namespace App\Storage\Presentation;

use App\Storage\Application\StorageProbe;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:storage:probe', description: 'Write a technical probe or verify all metadata checksums.')]
final class ProbeCommand extends Command
{
    public function __construct(private readonly StorageProbe $probe)
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addOption('write', null, InputOption::VALUE_REQUIRED, 'Opaque probe ID. Omit to verify only.');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getOption('write');
        if (is_string($id)) {
            $this->probe->write($id);
        }
        $failures = $this->probe->verify();
        $output->writeln(json_encode(['status' => $failures === [] ? 'ok' : 'failed', 'failures' => $failures], JSON_THROW_ON_ERROR));
        return $failures === [] ? Command::SUCCESS : Command::FAILURE;
    }
}
