<?php

namespace App\Models;

use App\Models\Team;
use App\Models\User;
use App\Models\Dataset;
use App\Models\Keyword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'collections';

    protected $fillable = [
        'name',
        'description',
        'image_link',
        'enabled',
        'keywords',
        'public',
        'counter',
        'mongo_object_id',
        'mongo_id',
        'created_at',
        'updated_at',
        'updated_on',
    ];
    
    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(Keyword::class, 'collection_has_keywords');
    }

    public function datasets(): BelongsToMany
    {
        return $this->belongsToMany(Dataset::class, 'collection_has_datasets')
        ->withPivot('collection_id', 'dataset_id', 'user_id', 'application_id', 'reason', 'created_at', 'updated_at');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'collection_has_datasets');
    }

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'collection_has_datasets');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
