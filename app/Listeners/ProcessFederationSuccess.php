<?php

namespace App\Listeners;

use App\Events\FederationProcessed;
use App\Jobs\SendEmailCustomIntegration;

class ProcessFederationSuccess
{
    /**
     * Handle the event.
     */
    public function handle(FederationProcessed $event): void
    {
        $federationId = $event->federation->id;
        $jobUuid      = $event->jobUuid;

        $event->federation->update(['is_running' => 0]);

        SendEmailCustomIntegration::dispatch($federationId, $jobUuid, 'success');

        \Log::info('Federation processed successfully', [
            'federation_id' => $federationId,
            'job_uuid'      => $jobUuid,
        ]);

    }
}
