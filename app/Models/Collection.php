<?php

namespace App\Models;

use App\Models\Dur;
use App\Models\Team;
use App\Models\Tool;
use App\Models\User;
use App\Models\Keyword;
use App\Models\Publication;
use App\Models\CollectionHasDatasetVersion;
use App\Http\Traits\DatasetFetch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Collection extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable, DatasetFetch;
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

    // Accessor for all datasets associated with this object
    public function getAllDatasetsAttribute()
    {
        return $this->getDatasetsViaDatasetVersion(
            CollectionHasDatasetVersion::class,
            'collection_id'
        );
    }

    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'collection_has_tools')
        ->withPivot('collection_id', 'tool_id', 'user_id', 'application_id', 'reason', 'created_at', 'updated_at');
    }

    public function dur(): BelongsToMany
    {
        return $this->belongsToMany(Dur::class, 'collection_has_durs')
        ->withPivot('collection_id', 'dur_id', 'user_id', 'application_id', 'reason', 'created_at', 'updated_at');
    }

    public function publications(): BelongsToMany
    {
        return $this->belongsToMany(Publication::class, 'collection_has_publications')
        ->withPivot('collection_id', 'publication_id', 'user_id', 'application_id', 'reason', 'created_at', 'updated_at');
    }
    public function userDatasets(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            CollectionHasDatasetVersion::class,
            'collection_id', // Foreign key on the CollectionHasDatasetVersion table
            'id',            // Local key on the Collection table
            'id',            // Local key on the User table
            'user_id'        // Foreign key on the CollectionHasDatasetVersion table
        );
    }

    public function userTools(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'collection_has_tools');
    }

    public function userPublications(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'collection_has_publications');
    }

    public function applicationDatasets(): HasManyThrough
    {
        return $this->hasManyThrough(
            Application::class,
            CollectionHasDatasetVersion::class,
            'collection_id', // Foreign key on the CollectionHasDatasetVersion table
            'id',            // Local key on the Collection table
            'id',            // Local key on the Application table
            'application_id' // Foreign key on the CollectionHasDatasetVersion table
        );
    }

    public function applicationTools(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'collection_has_tools');
    }

    public function applicationPublications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'collection_has_publications');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
