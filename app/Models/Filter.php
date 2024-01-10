<?php

namespace App\Models;

use App\Models\SavedSearch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Filter extends Model
{
    use HasFactory;

    protected $fillable = [
        'updated_at',
        'deleted_at',
        'type',
        'value',
        'keys',
        'enabled',
    ];

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'filters';

    /**
     * Specifically requests that Laravel casts the tiny ints as boolean
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Indicates if this model is timestamped
     * 
     * @var bool
     */
    public $timestamps = true;

    /**
     * Represents the filter type this filter is linked
     * to
     * 
     * @var string
     */
    private $type = '';

    /**
     * Represents the filter value
     * 
     * @var string
     */
    private $value = '';

    /**
     * Represents the filter key this filter is linked
     * to
     * 
     * @var string
     */
    private $keys = '';

    /**
     * Indicates whether this model is enabled or disabled
     * 
     * @var bool
     */
    private $enabled = false;

    /**
     * The saved searches that belong to the filter.
     */
    public function saved_searches(): BelongsToMany
    {
        return $this->belongsToMany(SavedSearch::class, 'saved_search_has_filters');
    }

}
