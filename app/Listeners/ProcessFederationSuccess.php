<?php

namespace App\Listeners;

use App\Events\FederationProcessed;

class ProcessFederationSuccess
{
    /**
     * Handle the event.
     */
    public function handle(FederationProcessed $event): void
    {
        $event->federation->update(['is_running' => 0]);

        \Log::info('Federation processed successfully', [
            'federation_id' => $event->federation->id,
        ]);

    }
}
