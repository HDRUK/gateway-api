<?php

namespace App\Observers;

use App\Models\Team;
use App\Http\Traits\IndexElastic;

class TeamObserver
{
    use IndexElastic;


    public function updated(Team $team): void
    {
        $this->reindexElasticDataProviderWithRelations((int) $team->id);
    }
}
