<?php

namespace App\Observers;

use App\Models\Dur;
use App\Http\Traits\IndexElastic;

class DurObserver
{
    use IndexElastic;

    /**
     * Handle the Dur "created" event.
     */
    public function created(Dur $dur): void
    {
        if($dur->status === Dur::STATUS_ACTIVE) {
            $this->indexElasticDur($dur->id);
        }
    }

    /**
     * Handle the Dur "updating" event.
     */
    public function updating(Dur $dur)
    {
        $dur->prevStatus = $dur->getOriginal('status'); // 'status' before updating
    }

    /**
     * Handle the Dur "updated" event.
     */
    public function updated(Dur $dur): void
    {
        $prevStatus = $dur->prevStatus;

        if($dur->status === Dur::STATUS_ACTIVE) {
            $this->indexElasticDur($dur->id);
        } elseif ($prevStatus === Dur::STATUS_ACTIVE) {
            $this->deleteDurFromElastic((int) $dur->id);
        }
    }

    /**
     * Handle the Dur "deleting" event.
     */
    public function deleting(Dur $dur)
    {
        $dur->prevStatus = $dur->getOriginal('status'); // 'status' before deleting
    }

    /**
     * Handle the Dur "deleted" event.
     */
    public function deleted(Dur $dur): void
    {
        $prevStatus = $dur->prevStatus;

        if($prevStatus === Dur::STATUS_ACTIVE) {
            $this->deleteDurFromElastic($dur->id);
        }
    }

    /**
     * Handle the Dur "restored" event.
     */
    public function restored(Dur $dur): void
    {
        //
    }

    /**
     * Handle the Dur "force deleted" event.
     */
    public function forceDeleted(Dur $dur): void
    {
        //
    }
}
