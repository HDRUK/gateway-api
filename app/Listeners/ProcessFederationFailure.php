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
        $event->federation->update(['is_running' => 0]);

        \Log::error('Federation processing failed', [
            'federation_id' => $event->federation->id,
            'error'         => $event->exception->getMessage(),
        ]);
    }
}
