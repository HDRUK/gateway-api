<?php

namespace App\Console\Commands\Helpers;

use App\Modules\Closer\CloserClient;
use App\Modules\Closer\Harvest\CloserWaveHarvester;
use App\Modules\Closer\Jobs\HarvestCloserWave;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class CloserHarvest extends Command
{
    protected $signature = 'closer:harvest
        {identifier : agency/id, urn:ddi:agency:id[:version], or a Discovery item URL}
        {--force : Harvest even if this version is already complete}
        {--queue : Dispatch the job instead of running now}';

    protected $description = 'Harvest a CLOSER wave (datasets, variables and statistics) into the closer_* tables';

    public function handle(CloserWaveHarvester $harvester): int
    {
        try {
            ['agency' => $agency, 'id' => $id] = CloserClient::parseIdentifier($this->argument('identifier'));
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('queue')) {
            HarvestCloserWave::dispatch($agency, $id, (bool) $this->option('force'));
            $this->info("Queued harvest of {$agency}/{$id}.");

            return self::SUCCESS;
        }

        $this->info("Harvesting {$agency}/{$id}...");
        $started = microtime(true);

        try {
            $wave = $harvester->harvest($agency, $id, (bool) $this->option('force'));
        } catch (Throwable $e) {
            $this->error('Harvest failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            '%s: version %s, %d datasets, %d variables (%.1fs, peak memory %dMB)',
            $wave->status,
            $wave->version,
            $wave->dataset_count,
            $wave->variable_count,
            microtime(true) - $started,
            memory_get_peak_usage(true) / 1048576,
        ));

        return self::SUCCESS;
    }
}
