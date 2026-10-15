<?php

declare(strict_types=1);

namespace App\Rendering\Presentation;

use App\Rendering\Application\Image;
use App\Rendering\Application\RenderCabinet;
use App\Rendering\Application\RenderRequest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:ai:render', description: 'Local conceptual furniture render from a PNG reference (may incur API charges).')]
final class RenderCommand extends Command
{
    public function __construct(private readonly RenderCabinet $render)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('reference', InputArgument::REQUIRED, 'Local PNG path')
            ->addArgument('instructions', InputArgument::REQUIRED, 'Material, lighting or surroundings to change')
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Save PNG to a new local file')
            ->addOption('resolution', null, InputOption::VALUE_REQUIRED, '1K, 2K or 4K', '1K')
            ->addOption('wait', null, InputOption::VALUE_REQUIRED, 'Maximum seconds to poll; 0 submits or checks once', '180');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $wait = filter_var($input->getOption('wait'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 600]]);
            if ($wait === false) {
                throw new \InvalidArgumentException('--wait must be an integer between 0 and 600.');
            }
            $destination = $input->getOption('output');
            if ($destination !== null && (file_exists($destination) || !is_writable(dirname($destination)))) {
                throw new \InvalidArgumentException('--output must be a new file in a writable directory.');
            }
            $source = (string) $input->getArgument('reference');
            if (!is_file($source) || !is_readable($source) || filesize($source) > 10485760) {
                throw new \InvalidArgumentException('Reference must be a readable local PNG up to 10 MiB.');
            }
            $bytes = file_get_contents($source);
            if ($bytes === false) {
                throw new \RuntimeException('Cannot read reference PNG.');
            }
            $request = new RenderRequest(new Image($bytes), (string) $input->getArgument('instructions'), (string) $input->getOption('resolution'));
            $deadline = microtime(true) + $wait;
            do {
                $record = $this->render->advance($request);
                if (in_array($record['status'], ['completed', 'failed', 'cancelled'], true) || microtime(true) >= $deadline) {
                    break;
                }
                usleep((int) (min(2.0, max(0.0, $deadline - microtime(true))) * 1000000));
            } while (microtime(true) < $deadline);
            if ($record['status'] === 'completed' && $destination !== null) {
                $file = @fopen($destination, 'xb');
                if ($file === false) {
                    throw new \RuntimeException('Cannot create output PNG. Cached render is preserved.');
                }
                try {
                    $image = $this->render->image($record['key']);
                    if (fwrite($file, $image->bytes) !== strlen($image->bytes)) {
                        throw new \RuntimeException('Cannot write output PNG. Cached render is preserved.');
                    }
                } finally {
                    fclose($file);
                }
            }
            $output->writeln(json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            return match ($record['status']) {
                'completed' => self::SUCCESS,
                'pending', 'processing' => 2,
                default => self::FAILURE,
            };
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
            return self::FAILURE;
        }
    }
}
