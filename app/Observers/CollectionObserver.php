<?php

namespace App\Observers;

use App\Models\Collection;
use App\Http\Traits\IndexElastic;

class CollectionObserver
{
    use IndexElastic;

    /**
     * Handle the Collection "created" event.
     */
    public function created(Collection $collection): void
    {
        if ($collection->status === Collection::STATUS_ACTIVE) {
            $this->indexElasticCollections((int) $collection->id);
        }
    }

    /**
     * Handle the Collection "updating" event.
     */
    public function updating(Collection $collection)
    {
        $collection->prevStatus = $collection->getOriginal('status'); // 'status' before updating
    }

    /**
     * Handle the Collection "updated" event.
     */
    public function updated(Collection $collection): void
    {
        $prevStatus = $collection->prevStatus;

        if ($collection->status === Collection::STATUS_ACTIVE) {
            $this->indexElasticCollections((int) $collection->id);
        } elseif ($prevStatus === Collection::STATUS_ACTIVE) {
            $this->deleteCollectionFromElastic((int) $collection->id);
        }
    }

    /**
     * Handle the Collection "deleting" event.
     */
    public function deleting(Collection $collection)
    {
        $collection->prevStatus = $collection->getOriginal('status'); // 'status' before deleting
    }

    /**
     * Handle the Collection "deleted" event.
     */
    public function deleted(Collection $collection): void
    {
        $prevStatus = $collection->prevStatus;

        if($prevStatus === Collection::STATUS_ACTIVE) {
            $this->deleteCollectionFromElastic((int) $collection->id);
        }
    }

    /**
     * Handle the Collection "restored" event.
     */
    public function restored(Collection $collection): void
    {
        //
    }

    /**
     * Handle the Collection "force deleted" event.
     */
    public function forceDeleted(Collection $collection): void
    {
        //
    }
}
