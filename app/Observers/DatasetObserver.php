<?php

namespace App\Observers;

use App\Models\Dataset;
use App\Modules\Concerns\HasModules;

class DatasetObserver
{
    use HasModules;

    /**
     * Handle the Dataset "created" event.
     */
    public function created(Dataset $dataset): void
    {
        $this->setActiveDate($dataset);
        $this->queueMissingEnrichment($dataset);
    }

    /**
     * Handle the Dataset "updated" event.
     */
    public function updated(Dataset $dataset): void
    {
        $this->setActiveDate($dataset);
        $this->queueMissingEnrichment($dataset);
    }

    /**
     * Queue a first pull for any enrichment module with nothing stored yet.
     * Off unless the ModuleManager flag is on and a module is enabled for the
     * dataset's partner_context. Never calls the source inline and never throws.
     */
    private function queueMissingEnrichment(Dataset $dataset): void
    {
        $this->modules($dataset)->refreshMissing();
    }

    private function setActiveDate(Dataset $dataset): void
    {
        if ($dataset->status === Dataset::STATUS_ACTIVE && $dataset->active_date === null) {
            $dataset->active_date = now();
            $dataset->save();
        }
    }
}
