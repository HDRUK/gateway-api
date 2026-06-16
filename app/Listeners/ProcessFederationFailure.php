<?php

namespace App\Listeners;

use App\Events\FederationProcessingFailed;

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

        \Log::error('Federation processing failed', [
            'federation_id' => $federationId,
            'error'         => $event->exception->getMessage(),
            'job_uuid'      => $jobUuid,
        ]);
    }
}
