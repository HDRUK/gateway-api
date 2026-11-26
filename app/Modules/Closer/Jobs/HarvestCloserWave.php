<?php

namespace App\Modules\Closer\Jobs;

use App\Modules\Closer\Harvest\CloserWaveHarvester;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Harvests one CLOSER wave: about 2 minutes and 110MB peak for the ALSPAC
 * pregnancy wave (8.3k variables). That is too heavy for the default queue
 * (128MB, 200s), so it runs on `enrichment` (256MB, 660s); one at a time
 * per wave.
 */
class HarvestCloserWave implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 1;

    /** Inside the enrichment supervisor's 660s and Redis retry_after (700s). */
    public int $timeout = 600;

    public int $uniqueFor = 1800;

    public function __construct(
        public readonly string $agency,
        public readonly string $identifier,
        public readonly bool $force = false,
    ) {
        $this->onQueue('enrichment');
    }

    public function uniqueId(): string
    {
        return "{$this->agency}/{$this->identifier}";
    }

    public function handle(CloserWaveHarvester $harvester): void
    {
        $harvester->harvest($this->agency, $this->identifier, $this->force);
    }
}
