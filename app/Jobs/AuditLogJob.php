<?php

namespace App\Jobs;

use App\Services\CloudPubSubService;
use CloudLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AuditLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;
    protected CloudPubSubService $cloudPubSub;

    public function __construct(array $auditLog)
    {
        $this->data = $auditLog;
    }

    /**
     * Execute the job.
     */
    public function handle(CloudPubSubService $cloudPubSub): void
    {
        $this->cloudPubSub = $cloudPubSub;
        $publish = $this->cloudPubSub->publishMessage($this->data);
        CloudLogger::write('Message sent to pubsub from "SendAuditLogToPubSub" job ' . json_encode($publish));
        CloudLogger::write($this->data);
    }
}
