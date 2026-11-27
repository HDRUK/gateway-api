<?php

namespace App\Modules\Closer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A CLOSER StudyUnit (one wave of a study) and the state of its harvest. */
class CloserWave extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_HARVESTING = 'harvesting';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_FAILED = 'failed';

    protected $table = 'closer_waves';

    protected $fillable = [
        'agency',
        'identifier',
        'version',
        'title',
        'status',
        'dataset_count',
        'variable_count',
        'harvested_at',
        'error',
    ];

    protected $casts = [
        'version' => 'integer',
        'dataset_count' => 'integer',
        'variable_count' => 'integer',
        'harvested_at' => 'immutable_datetime',
    ];

    public function datasets(): HasMany
    {
        return $this->hasMany(CloserDataset::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(CloserQuestion::class);
    }

    public function variables(): HasMany
    {
        return $this->hasMany(CloserVariable::class);
    }
}
