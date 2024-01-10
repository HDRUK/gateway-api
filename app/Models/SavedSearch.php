<?php

namespace App\Models;

use App\Models\Filter;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SavedSearch extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

    protected $fillable = [
        'updated_at',
        'deleted_at',
        'name',
        'search_term',
        'enabled',
    ];

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'saved_searches';

    /**
     * Indicates if this model is timestamped
     * 
     * @var bool
     */
    public $timestamps = true;

    /**
     * Indicates the name of the saved search
     * 
     * @var string
     */
    private $name = '';

    /**
     * Indicates the search term of the saved search
     * 
     * @var string
     */
    private $search_term = '';

    /**
     * Indicates whether this model is enabled or disabled
     * 
     * @var bool
     */
    private $enabled = false;

    /**
     * The filters that belong to the saved search.
     */
    public function filters(): BelongsToMany
    {
        return $this->belongsToMany(Filter::class, 'saved_search_has_filters');
    }
}
