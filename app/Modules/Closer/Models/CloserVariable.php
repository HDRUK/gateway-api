<?php

namespace App\Modules\Closer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A CLOSER Variable with its statistics.
 *
 * `frequencies` holds RAW counts per answer value, as published by CLOSER.
 * They must be rounded and small counts suppressed before any endpoint or
 * page shows them. Never return this column as-is.
 */
class CloserVariable extends Model
{
    protected $table = 'closer_variables';

    protected $fillable = [
        'closer_wave_id',
        'closer_dataset_id',
        'closer_question_id',
        'identifier',
        'version',
        'name',
        'label',
        'question_identifier',
        'data_type',
        'summary_statistics',
        'frequencies',
    ];

    protected $casts = [
        'version' => 'integer',
        'summary_statistics' => 'array',
        'frequencies' => 'array',
    ];

    public function wave(): BelongsTo
    {
        return $this->belongsTo(CloserWave::class, 'closer_wave_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(CloserQuestion::class, 'closer_question_id');
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(CloserDataset::class, 'closer_dataset_id');
    }
}
