<?php

namespace App\Observers;

use App\Models\DataAccessApplication;
use App\Models\DataAccessApplicationStatus;

class DataAccessApplicationObserver
{
    /**
     * Handle the DataAccessApplication "updated" event.
     */
    public function updated(DataAccessApplication $application): void
    {
        if ($application->wasChanged('approval_status')) {
            DataAccessApplicationStatus::create([
                'application_id' => $application->id,
                'approval_status' => $application->approval_status,
            ]);
        }

        if ($application->wasChanged('submission_status')) {
            DataAccessApplicationStatus::create([
                'application_id' => $application->id,
                'submission_status' => $application->submission_status,
            ]);
        }
    }
}
