<?php

namespace App\Models;

use App\Models\NamedEntities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Dataset extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_ARCHIVED = 'ARCHIVED';

    public const ORIGIN_MANUAL = 'MANUAL';
    public const ORIGIN_API = 'API';
    public const ORIGIN_FMA = 'FMA';

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'datasets';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'team_id',
        'label',
        'short_description',
        'datasetid',
        'dataset',
        'created',
        'updated',
        'submitted',
        'pid',
        'version',
        'create_origin',
        'status',
    ];

    /**
     * The named_entities that belong to the dataset.
     */
    public function namedEntities(): BelongsToMany
    {
        return $this->belongsToMany(NamedEntities::class, 'dataset_has_named_entities');
    }

    public function shouldFinalise(): bool
    {
        if ($this->create_origin !== Dataset::ORIGIN_MANUAL) {
            return true;
        } else {
            if ($this->status === Dataset::STATUS_ACTIVE) {
                return true;
            }
        }

        return false;
    }
}
