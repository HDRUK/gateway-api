<?php

namespace App\Listeners;

use App\Enums\FederationJobRunStatus;
use App\Events\FederationProcessingFailed;
use App\Jobs\SendEmailCustomIntegration;
use App\Models\FederationJobRun;

class ProcessFederationFailure
{
    /**
     * Create the event listener.
     */
    public function handle(FederationProcessingFailed $event): void
    {
        $federationId = $event->federation->id;
        $jobUuid      = $event->jobUuid;
        $errorText    = substr($event->exception->getMessage(), 0, 200);

        $event->federation->update([
            'is_running' => 0,
            'error' => true,
            'error_text' => $errorText,
            'enabled' => false,
        ]);

        if ($jobUuid !== null) {
            FederationJobRun::closeRun($federationId, $jobUuid, FederationJobRunStatus::FAILED, $errorText);
        }

        SendEmailCustomIntegration::dispatch($federationId, $jobUuid, 'failure', $event->exception->getMessage());

        \Log::error('Federation processing failed', [
            'federation_id' => $federationId,
            'error'         => $event->exception->getMessage(),
            'job_uuid'      => $jobUuid,
        ]);
    }
}
