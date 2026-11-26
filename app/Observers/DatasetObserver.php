<?php

namespace App\Observers;

use App\Models\Dataset;

class DatasetObserver
{
    /**
     * Handle the Dataset "created" event.
     */
    public function created(Dataset $dataset): void
    {
        $this->setActiveDate($dataset);
    }

    /**
     * Handle the Dataset "updated" event.
     */
    public function updated(Dataset $dataset): void
    {
        $this->setActiveDate($dataset);
    }

    private function setActiveDate(Dataset $dataset): void
    {
        if ($dataset->status === Dataset::STATUS_ACTIVE && $dataset->active_date === null) {
            $dataset->active_date = now();
            $dataset->save();
        }
    }
}
