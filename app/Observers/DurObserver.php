<?php

namespace App\Observers;

use App\Models\Dur;
use App\Jobs\ExtractDatasetFromDur;

class DurObserver
{
    /**
     * Handle the Dur "created" event.
     */
    public function created(Dur $dur): void
    {
        ExtractDatasetFromDur::dispatch($dur->id);
        $this->setActiveDate($dur);
    }

    /**
     * Handle the Dur "updated" event.
     */
    public function updated(Dur $dur): void
    {
        ExtractDatasetFromDur::dispatch($dur->id);
        $this->setActiveDate($dur);
    }

    private function setActiveDate(Dur $dur): void
    {
        if ($dur->status === Dur::STATUS_ACTIVE && $dur->active_date === null) {
            $dur->active_date = now();
            $dur->withoutEvents(function () use ($dur) {
                $dur->save();
            });
        }
    }
}
