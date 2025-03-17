<?php

namespace App\Observers;

use App\Models\Publication;
use App\Http\Traits\IndexElastic;

class PublicationObserver
{
    use IndexElastic;

    /**
     * Handle the Publication "created" event.
     */
    public function created(Publication $publication): void
    {
        if ($publication->status === Publication::STATUS_ACTIVE) {
            $this->indexElasticPublication((int) $publication->id);
        }
    }

    /**
     * Handle the Publication "updating" event.
     */
    public function updating(Publication $publication)
    {
        $publication->prevStatus = $publication->getOriginal('status'); // 'status' before updating
    }

    /**
     * Handle the Publication "updated" event.
     */
    public function updated(Publication $publication): void
    {
        $prevStatus = $publication->prevStatus;

        if ($prevStatus === Publication::STATUS_ACTIVE && $publication->status !== Publication::STATUS_ACTIVE) {
            $this->deletePublicationFromElastic((int) $publication->id);
        }

        if ($publication->status === Publication::STATUS_ACTIVE) {
            $this->indexElasticPublication((int) $publication->id);
        }
    }

    /**
     * Handle the Collection "deleting" event.
     */
    public function deleting(Publication $publication)
    {
        $publication->prevStatus = $publication->getOriginal('status'); // 'status' before deleting
    }

    /**
     * Handle the Publication "deleted" event.
     */
    public function deleted(Publication $publication): void
    {
        $prevStatus = $publication->prevStatus;

        if ($prevStatus === Publication::STATUS_ACTIVE) {
            $this->deletePublicationFromElastic((int) $publication->id);
        }
    }

    /**
     * Handle the Publication "restored" event.
     */
    public function restored(Publication $publication): void
    {
        //
    }

    /**
     * Handle the Publication "force deleted" event.
     */
    public function forceDeleted(Publication $publication): void
    {
        //
    }
}
