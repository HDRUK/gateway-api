<?php

namespace App\Jobs;

use App\Services\CloudLoggerService;
use App\Services\CloudPubSubService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Laravel\Horizon\Contracts\Silenced;

class AuditLogJob implements ShouldQueue, Silenced
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    protected array $data;

    public function __construct(array $auditLog)
    {
        $this->data = $auditLog;
    }

    /**
     * Execute the job.
     */
    public function handle(CloudPubSubService $cloudPubSub, CloudLoggerService $cloudLogger): void
    {
        $publish = $cloudPubSub->publishMessage($this->data);
        $cloudLogger->write('Message sent to pubsub from "SendAuditLogToPubSub" job ' . json_encode($publish));
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'audit'
        ];
    }
}
