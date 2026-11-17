<?php

namespace App\Observers;

use App\Models\Collection;

class CollectionObserver
{
    /**
     * Handle the Collection "created" event.
     */
    public function created(Collection $collection): void
    {
        $this->setActiveDate($collection);
    }

    /**
     * Handle the Collection "updated" event.
     */
    public function updated(Collection $collection): void
    {
        $this->setActiveDate($collection);
    }

    private function setActiveDate(Collection $collection): void
    {
        if ($collection->status === Collection::STATUS_ACTIVE && $collection->active_date === null) {
            $collection->active_date = now();
            $collection->withoutEvents(function () use ($collection) {
                $collection->save();
            });
        }
    }
}
