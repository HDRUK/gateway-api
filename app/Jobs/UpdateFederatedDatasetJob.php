<?php

namespace App\Jobs;

use App\Models\Dataset;
use App\Models\Federation;
use App\Services\GatewayMetadataIngestionService;
use App\Services\GoogleSecretManagerService;
use App\Traits\GatewayMetadataIngestionTrait;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class UpdateFederatedDatasetJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use GatewayMetadataIngestionTrait;

    public int $tries = 1;
    public int $timeout = 90;

    public function __construct(
        private readonly Federation $federation,
        private readonly string $pid,
        private readonly array $data,
        private readonly Dataset $local,
        private readonly string $jobUuid,
        private readonly int $attempts,
    ) {
        $this->onQueue('federation');
    }

    public function handle(GatewayMetadataIngestionService $gmi): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $gmi->setTeam($this->federation->team[0]->id);

        $gsms = app(GoogleSecretManagerService::class);

        $this->updateFederatedDataset(
            $this->federation,
            $this->pid,
            $this->data,
            $this->local,
            $gsms,
            $gmi,
            $this->jobUuid,
            $this->attempts,
        );
    }

    public function failed(Throwable $exception): void
    {
        $this->recordUnexpectedFailure(
            $this->federation->team[0]->id,
            $this->federation->id,
            $this->pid,
            $this->jobUuid,
            $this->attempts,
            'updating',
        );
    }
}
