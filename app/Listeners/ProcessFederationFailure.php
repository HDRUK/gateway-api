<?php

namespace App\Listeners;

use App\Events\FederationProcessingFailed;
use App\Jobs\SendEmailCustomIntegration;

class ProcessFederationFailure
{
    /**
     * Create the event listener.
     */
    public function handle(FederationProcessingFailed $event): void
    {
        $federationId = $event->federation->id;
        $jobUuid      = $event->jobUuid;

        $event->federation->update(['is_running' => 0]);

        SendEmailCustomIntegration::dispatch($federationId, $jobUuid, 'failure', $event->exception->getMessage());

        \Log::error('Federation processing failed', [
            'federation_id' => $federationId,
            'error'         => $event->exception->getMessage(),
            'job_uuid'      => $jobUuid,
        ]);
    }
}
