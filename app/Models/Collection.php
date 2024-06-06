<?php

namespace App\Models;

use App\Models\Dur;
use App\Models\Team;
use App\Models\Tool;
use App\Models\User;
use App\Models\Dataset;
use App\Models\Keyword;
use App\Models\Publication;
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

    public function userDatasets(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'collection_has_datasets');
    }

    public function userTools(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'collection_has_tools');
    }

    public function userPublications(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'collection_has_publications');
    }

    public function applicationDatasets(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'collection_has_datasets');
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
