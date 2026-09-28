<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NightlyDatasetTest extends Model
{
    protected $table = 'nightly_dataset_tests';

    protected $fillable = [
        'dataset_id',
        'status_code',
    ];

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status_code !== null && $this->status_code >= 200 && $this->status_code < 400;
    }
}
