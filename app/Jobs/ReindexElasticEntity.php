<?php

namespace App\Jobs;

use App\Http\Traits\IndexElastic;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use InvalidArgumentException;

/**
 * Reindexes a single dataset/collection/dur/tool. Dispatched one-per-entity
 * (via a Bus::batch()) from IndexElastic::reindexElasticDataProviderWithRelations()
 * instead of looping over a team's entire relation set inside one job — keeps
 * each job's runtime constant regardless of team size.
 */
class ReindexElasticEntity implements ShouldQueue
{
    use Batchable;
    use Queueable;
    use IndexElastic;

    public $timeout = 90;

    public function __construct(
        private readonly string $type,
        private readonly int|string $id
    ) {
        $this->onQueue('indexing');
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        match ($this->type) {
            'dataset' => $this->reindexElastic((string) $this->id),
            'collection' => $this->indexElasticCollections((int) $this->id),
            'dur' => $this->indexElasticDur((string) $this->id),
            'tool' => $this->indexElasticTools((int) $this->id),
            default => throw new InvalidArgumentException("Unknown reindex entity type [{$this->type}]"),
        };
    }
}
