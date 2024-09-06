<?php

namespace App\Models;

use App\Http\Traits\DatasetFetch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NamedEntities extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;
    use DatasetFetch;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'named_entities';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'name'
    ];

    /**
     * Name of this named_entity
     *
     * @var string
     */
    private $name = '';

    // Accessor for all datasets associated with this object
    public function getAllDatasetsAttribute()
    {
        return $this->getDatasetsViaDatasetVersion(
            new DatasetVersionHasNamedEntities(),
            'named_entities_id'
        );
    }

    /**
     * Retrieve versions associated with this named entity.
     */
    public function versions()
    {
        return $this->belongsToMany(DatasetVersion::class, 'dataset_version_has_named_entities', 'named_entities_id', 'dataset_version_id');
    }
}
