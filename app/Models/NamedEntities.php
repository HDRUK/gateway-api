<?php

namespace App\Models;

use App\Models\Dataset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class NamedEntities extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

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

    /**
     * The datasets that belong to the named_entity.
     */
    public function datasets(): BelongsToMany
    {
        return $this->belongsToMany(Dataset::class, 'dataset_has_named_entities');
    }
}
