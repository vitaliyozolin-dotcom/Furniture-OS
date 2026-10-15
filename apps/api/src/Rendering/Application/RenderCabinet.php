<?php

declare(strict_types=1);

namespace App\Rendering\Application;

final readonly class RenderCabinet
{
    /** @param iterable<ImageGenerator> $generators */
    public function __construct(private iterable $generators, private RenderJournal $journal, private bool $enabled, private string $provider, private int $maxCostKopecks, private int $budgetKopecks)
    {
    }

    public function advance(RenderRequest $request): array
    {
        if (!$this->enabled) {
            throw new \RuntimeException('AI is disabled. Set AI_ENABLED=1 in apps/api/.env.local.');
        }
        $generator = $this->generator();
        $generator->assertConfigured();
        if ($this->maxCostKopecks < 1 || $this->budgetKopecks < $this->maxCostKopecks) {
            throw new \RuntimeException('Invalid AI request limit or total budget.');
        }
        $key = $request->key($generator->name(), $generator->model());
        return $this->journal->exclusive(function () use ($request, $generator, $key): array {
            $records = $this->journal->records();
            $record = $records[$key] ?? null;
            if ($record !== null) {
                if ($record['status'] === 'completed') {
                    return $record;
                }
                if (in_array($record['status'], ['uncertain', 'failed', 'cancelled'], true)) {
                    throw new \RuntimeException('Render is '.$record['status'].'. Reconcile the journal with provider billing before any new paid attempt.');
                }
                $generation = $generator->poll($record['providerJobId']);
            } else {
                // Unknown/failed attempts retain their reservation: a timeout does not prove that billing stopped.
                $reserved = array_sum(array_column($records, 'reservedKopecks'));
                if ($reserved + $this->maxCostKopecks > $this->budgetKopecks) {
                    throw new \RuntimeException('AI experiment budget exhausted.');
                }
                $record = [
                    'key' => $key, 'provider' => $generator->name(), 'model' => $generator->model(),
                    'promptVersion' => RenderRequest::PROMPT_VERSION, 'inputChecksum' => hash('sha256', $request->reference->bytes),
                    'resolution' => $request->resolution, 'status' => 'uncertain', 'providerJobId' => null,
                    'reservedKopecks' => $this->maxCostKopecks, 'costKopecks' => null, 'currency' => 'RUB',
                    'createdAt' => gmdate('c'), 'startedAt' => microtime(true), 'retryCount' => 0, 'conceptual' => true,
                ];
                // Commit before POST. Crashes and ambiguous transport errors must never cause another POST.
                $this->journal->save($key, $record);
                $generation = $generator->start($request, $this->maxCostKopecks);
            }
            $record['providerJobId'] = $generation->id;
            $record['status'] = $generation->status;
            $record['costKopecks'] = $generation->costKopecks ?? $record['costKopecks'];
            $record['reservedKopecks'] = max($record['reservedKopecks'], $generation->costKopecks ?? 0);
            $record['elapsedMs'] = (int) round((microtime(true) - $record['startedAt']) * 1000);
            if ($generation->image !== null) {
                $this->journal->saveImage($key, $generation->image);
            }
            $this->journal->save($key, $record);
            return $record;
        });
    }

    public function image(string $key): Image
    {
        return $this->journal->image($key);
    }

    private function generator(): ImageGenerator
    {
        foreach ($this->generators as $generator) {
            if ($generator->name() === $this->provider) {
                return $generator;
            }
        }
        throw new \RuntimeException('Unknown AI_PROVIDER.');
    }
}
