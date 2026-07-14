<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property int|null $dataset_version_id
 * @property string|null $name
 * @property string|null $description
 * @property-read \Illuminate\Support\Carbon|null $created_at
 * @property-read \Illuminate\Support\Carbon|null $updated_at
 */
class StructuralTable extends Model
{
    protected $table = 'gwdm30_structural_tables';

    protected $fillable = [
        'dataset_version_id',
        'name',
        'description',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }

    public function columns(): HasMany
    {
        return $this->hasMany(StructuralColumn::class, 'gwdm30_structural_table_id');
    }
}
