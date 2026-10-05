<?php

namespace App\Jobs;

use App\Models\Federation;
use App\Services\GatewayMetadataIngestionService;
use App\Traits\GatewayMetadataIngestionTrait;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ArchiveFederatedDatasetsChunk implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use GatewayMetadataIngestionTrait;

    public int $tries = 1;
    public int $timeout = 60;

    public function __construct(
        private readonly Federation $federation,
        private readonly array $pids,
    ) {
        $this->onQueue('federation');
    }

    public function handle(GatewayMetadataIngestionService $gmi): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $gmi->setTeam($this->federation->team[0]->id);

        foreach ($this->pids as $pid) {
            $this->archiveFederatedDataset($pid, $gmi);
        }
    }
}
