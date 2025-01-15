<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Observers\DurHasDatasetVersionObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([DurHasDatasetVersionObserver::class])]
class DatasetVersionHasDatasetVersion extends Model
{
    use HasFactory;

    public const LINKAGE_TYPE_DATASETS = 'linkedDatasets';
    public const LINKAGE_TYPE_DERIVED_FROM = 'isDerivedFrom';
    public const LINKAGE_TYPE_PART_OF = 'isPartOf';
    public const LINKAGE_TYPE_MEMBER_OF = 'isMemberOf';

    protected $fillable = [
        'dataset_version_source_id',
        'dataset_version_target_id',
        'linkage_type',
        'direct_linkage',
        'description',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'dataset_version_has_dataset_version';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the first dataset linked by this linkage.
     */
    public function dataset1()
    {
        return $this->belongsTo(Dataset::class, 'dataset_version_source_id');
    }

    /**
     * Get the second dataset linked by this linkage.
     */

    public function dataset2()
    {
        return $this->belongsTo(Dataset::class, 'dataset_version_target_id');
    }
}
