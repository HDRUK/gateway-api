<?php

namespace App\Observers;

use App\Models\DataAccessApplication;
use App\Models\DataAccessApplicationStatus;

class DataAccessApplicationObserver
{
    // /**
    //  * Handle the Collection "updating" event.
    //  */
    // public function updating(Collection $collection)
    // {
    //     $collection->prevStatus = $collection->getOriginal('status'); // 'status' before updating
    // }

    /**
     * Handle the DataAccessApplication "updated" event.
     */
    public function updated(DataAccessApplication $application): void
    {
        \Log::info('in updated observation');
        if ($application->wasChanged('approval_status')) {
            DataAccessApplicationStatus::create([
                'application_id' => $application->id,
                'approval_status' => $application->approval_status,
            ]);
        }
    }
}
