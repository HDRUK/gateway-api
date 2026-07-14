<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
