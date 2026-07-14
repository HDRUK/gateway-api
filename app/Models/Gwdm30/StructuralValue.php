<?php

namespace App\Models\Gwdm30;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int|null $gwdm30_structural_column_id
 * @property string|null $name
 * @property string|null $description
 * @property int|null $frequency
 * @property-read \Illuminate\Support\Carbon|null $created_at
 * @property-read \Illuminate\Support\Carbon|null $updated_at
 */
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
