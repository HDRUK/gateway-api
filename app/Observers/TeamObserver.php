<?php

namespace App\Observers;

use App\Models\Team;
use App\Http\Traits\IndexElastic;

class TeamObserver
{
    use IndexElastic;

    /**
     * Handle the Team "updated" event.
     */
    public function updated(Team $team): void
    {
        $this->reindexElasticDataProviderWithRelations((int) $team->id);
    }

    /**
     * Handle the Team "updated" event.
     */
    public function deleted(Team $team): void
    {
        $this->deleteDataProvider((int) $team->id);
    }
}
