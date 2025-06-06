<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SavedSearch extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;

    protected $fillable = [
        'updated_at',
        'deleted_at',
        'name',
        'search_term',
        'search_endpoint',
        'enabled',
        'user_id',
        'sort_order',
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
     * Indicates the search endpoint of the saved search
     *
     * @var string
     */
    private $search_endpoint = '';

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
        return $this->belongsToMany(Filter::class, 'saved_search_has_filters')
            ->withPivot('saved_search_id', 'filter_id', 'terms');
    }
}
