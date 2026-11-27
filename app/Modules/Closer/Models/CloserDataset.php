<?php

namespace App\Modules\Closer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A CLOSER PhysicalInstance: one data file within a wave. */
class CloserDataset extends Model
{
    protected $table = 'closer_datasets';

    protected $fillable = [
        'closer_wave_id',
        'identifier',
        'version',
        'title',
        'file_name',
        'case_quantity',
    ];

    protected $casts = [
        'version' => 'integer',
        'case_quantity' => 'integer',
    ];

    public function wave(): BelongsTo
    {
        return $this->belongsTo(CloserWave::class, 'closer_wave_id');
    }

    public function variables(): HasMany
    {
        return $this->hasMany(CloserVariable::class);
    }
}
