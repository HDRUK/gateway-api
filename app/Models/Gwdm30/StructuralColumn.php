<?php

namespace App\Models\Gwdm30;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property int|null $gwdm30_structural_table_id
 * @property string|null $name
 * @property string|null $data_type
 * @property string|null $description
 * @property bool|null $sensitive
 * @property-read \Illuminate\Support\Carbon|null $created_at
 * @property-read \Illuminate\Support\Carbon|null $updated_at
 */
class StructuralColumn extends Model
{
    protected $table = 'gwdm30_structural_columns';

    protected $fillable = [
        'gwdm30_structural_table_id',
        'name',
        'data_type',
        'description',
        'sensitive',
    ];

    protected $casts = [
        'sensitive' => 'boolean',
    ];

    public function structuralTable(): BelongsTo
    {
        return $this->belongsTo(StructuralTable::class, 'gwdm30_structural_table_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(StructuralValue::class, 'gwdm30_structural_column_id');
    }
}
