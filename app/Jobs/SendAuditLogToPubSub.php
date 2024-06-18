<?php

namespace App\Jobs;

use Config;
use Illuminate\Bus\Queueable;
use App\Services\PubSubService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendAuditLogToPubSub implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array $auditLog)
    {
        $this->data = $auditLog;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!env('GOOGLE_CLOUD_PUBSUB_ENABLED', false)) {
            return;
        }

        $pubSubService = new PubSubService();
        $pubSubService->publishMessage($this->data);
    }
}
