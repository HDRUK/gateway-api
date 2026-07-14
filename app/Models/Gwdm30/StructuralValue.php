<?php

namespace App\Models\Gwdm30;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StructuralValue extends Model
{
    protected $table = 'gwdm30_structural_values';

    protected $fillable = [
        'gwdm30_structural_column_id',
        'name',
        'description',
        'frequency',
    ];

    protected $casts = [
        'frequency' => 'integer',
    ];

    public function structuralColumn(): BelongsTo
    {
        return $this->belongsTo(StructuralColumn::class, 'gwdm30_structural_column_id');
    }
}
