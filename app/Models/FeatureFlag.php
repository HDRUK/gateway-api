<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    public const KEY_COHORT_DISCOVERY_SERVICE = 'CohortDiscoveryService';

    public const KEY_RQUEST = 'RQuest';

    protected $fillable = ['key', 'enabled'];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
