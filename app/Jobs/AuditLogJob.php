<?php

namespace App\Jobs;

use App\Services\CloudLoggerService;
use App\Services\CloudPubSubService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AuditLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 10;

    protected array $data;
    protected CloudPubSubService $cloudPubSub;
    protected CloudLoggerService $cloudLogger;

    public function __construct(array $auditLog)
    {
        $this->data = $auditLog;
    }

    /**
     * Execute the job.
     */
    public function handle(CloudPubSubService $cloudPubSub, CloudLoggerService $cloudLogger): void
    {
        $this->cloudPubSub = $cloudPubSub;
        $this->cloudLogger = $cloudLogger;
        $publish = $this->cloudPubSub->publishMessage($this->data);
        $this->cloudLogger->write('Message sent to pubsub from "SendAuditLogToPubSub" job ' . json_encode($publish));
    }
}
