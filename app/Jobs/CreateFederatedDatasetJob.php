<?php

namespace App\Jobs;

use App\Models\Dataset;
use App\Models\Federation;
use App\Services\GatewayMetadataIngestionService;
use App\Services\GoogleSecretManagerService;
use App\Services\Gwdm\GwdmMetadataHandler;
use App\Traits\GatewayMetadataIngestionTrait;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateFederatedDatasetJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use GatewayMetadataIngestionTrait;

    public int $tries = 1;
    public int $timeout = 180;

    public function __construct(
        private readonly Federation $federation,
        private readonly string $pid,
        private readonly array $data,
        private readonly string $jobUuid,
        private readonly int $attempts,
    ) {
        $this->onQueue('federation');
    }

    public function handle(GatewayMetadataIngestionService $gmi, GwdmMetadataHandler $handler): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $gmi->setTeam($this->federation->team[0]->id);

        $existsAlready = Dataset::where([
            'pid' => $this->pid,
            'team_id' => $gmi->getTeam(),
        ])->exists();

        if ($existsAlready) {
            $this->log('info', "attempted to re-create a dataset that already exists @ {$this->pid}");
            return;
        }

        $gsms = app(GoogleSecretManagerService::class);

        $this->createFederatedDataset(
            $this->federation,
            $this->pid,
            $this->data,
            $gsms,
            $gmi,
            $this->jobUuid,
            $this->attempts,
            $handler,
        );
    }
}
