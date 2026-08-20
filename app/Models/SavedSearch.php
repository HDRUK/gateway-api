<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @OA\Schema(
 *   schema="SavedSearch",
 *   description="A user's saved search definition",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="user_id", type="integer", nullable=true, example=42),
 *   @OA\Property(property="name", type="string", example="My saved search"),
 *   @OA\Property(property="search_term", type="string", nullable=true, example="cancer registry"),
 *   @OA\Property(property="search_endpoint", type="string", nullable=true, example="datasets"),
 *   @OA\Property(
 *     property="sort_order",
 *     type="string",
 *     enum={"score:desc","name:asc","name:desc","created_at:asc","created_at:desc"},
 *     example="score:desc"
 *   ),
 *   @OA\Property(property="enabled", type="boolean", example=true),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-01T08:00:00Z"),
 *   @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null),
 * )
 */
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
