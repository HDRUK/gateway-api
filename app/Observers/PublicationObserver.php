<?php

namespace App\Observers;

use App\Models\Publication;

class PublicationObserver
{
    /**
     * Handle the Publication "created" event.
     */
    public function created(Publication $publication): void
    {
        $this->setActiveDate($publication);
    }

    /**
     * Handle the Publication "updated" event.
     */
    public function updated(Publication $publication): void
    {
        $this->setActiveDate($publication);
    }

    private function setActiveDate(Publication $publication): void
    {
        if ($publication->status === Publication::STATUS_ACTIVE && $publication->active_date === null) {
            $publication->active_date = now();
            $publication->withoutEvents(function () use ($publication) {
                $publication->save();
            });
        }
    }
}
