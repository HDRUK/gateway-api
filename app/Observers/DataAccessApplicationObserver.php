<?php

namespace App\Observers;

use App\Models\DataAccessApplication;
use App\Models\DataAccessApplicationStatus;

class DataAccessApplicationObserver
{
    /**
     * Handle the DataAccessApplication "updated" event.
     */
    public function updated(DataAccessApplication $dar): void
    {
        if ($dar->wasChanged('approval_status')) {
            DataAccessApplicationStatus::create([
                'application_id' => $dar->id,
                'approval_status' => $dar->approval_status,
                'submission_status' => null,
                'review_id' => $dar->status_review_id,
                'team_id' => $dar->team_id,
            ]);
        }

        if ($dar->wasChanged('submission_status')) {
            DataAccessApplicationStatus::create([
                'application_id' => $dar->id,
                'approval_status' => null,
                'submission_status' => $dar->submission_status,
                'review_id' => $dar->status_review_id,
                'team_id' => $dar->team_id,
            ]);
        }
    }
}
