<?php

namespace App\Observers;

use App\Models\TeamHasDataAccessApplication;
use App\Models\DataAccessApplicationStatus;

class TeamHasDataAccessApplicationObserver
{
    /**
     * Handle the DataAccessApplication "updated" event.
     */
    public function updated(TeamHasDataAccessApplication $teamHasDar): void
    {
        if ($teamHasDar->wasChanged('approval_status')) {
            DataAccessApplicationStatus::create([
                'application_id' => $teamHasDar->dar_applicantion_id,
                'approval_status' => $teamHasDar->approval_status,
                'submission_status' => null,
                'review_id' => $teamHasDar->review_id,
                'team_id' => $teamHasDar->team_id,
            ]);
        }

        if ($teamHasDar->wasChanged('submission_status')) {
            DataAccessApplicationStatus::create([
                'application_id' => $teamHasDar->dar_applicantion_id,
                'approval_status' => null,
                'submission_status' => $teamHasDar->approval_status,
                'review_id' => $teamHasDar->review_id,
                'team_id' => $teamHasDar->team_id,
            ]);
        }
    }
}
