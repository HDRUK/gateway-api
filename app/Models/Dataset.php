<?php

namespace App\Models;

use App\Models\NamedEntities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dataset extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

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
    ];

    /**
     * The named_entities that belong to the dataset.
     */
    public function named_entities(): HasMany
    {
        return $this->hasMany(NamedEntities::class);
    }
}
