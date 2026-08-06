<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @OA\Schema(
 *   schema="TypeCategory",
 *   description="A category used to classify tool types",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="name", type="string", example="Analysis"),
 *   @OA\Property(property="description", type="string", nullable=true, example="Tools used for data analysis"),
 *   @OA\Property(property="enabled", type="boolean", example=true),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-01T08:00:00Z"),
 * )
 */
class TypeCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Table associated with this model
     *
     * @var string
     */
    public $table = 'type_categories';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Represents the name of this tool type category
     *
     * @var string
     */
    private $name = '';

    /**
     * Represents the description of this tool type category
     *
     * @var string
     */
    private $description = '';

    /**
     * Whether or not this name is enabled
     *
     * @var boolean
     */
    private $enabled = false;

    /**
     * The tools that belong to the type category.
     */
    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'tool_has_type_category');
    }
}
