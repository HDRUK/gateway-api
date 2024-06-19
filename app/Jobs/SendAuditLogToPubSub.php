<?php

namespace App\Jobs;

use Config;
use Illuminate\Bus\Queueable;
use App\Services\PubSubService;
use App\Services\LoggingService;
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
        if (!Config::get('services.googlepubsub.enabled')) {
            return;
        }

        $pubSubService = new PubSubService();
        $publish = $pubSubService->publishMessage($this->data);

        if (!Config::get('services.googlelogging.enabled')) {
            return;
        }

        $loggingService = new LoggingService();
        $loggingService->writeLog('Message sent to pubsub from "SendAuditLogToPubSub" job ' . json_encode($publish));
    }
}
