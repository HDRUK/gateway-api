<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *   schema="ProgrammingLanguage",
 *   description="A programming language available for tagging tools",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="name", type="string", example="Python"),
 *   @OA\Property(property="enabled", type="boolean", example=true),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-01T08:00:00Z"),
 * )
 */
class ProgrammingLanguage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
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
    public $table = 'programming_languages';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Represents the name of this programming language
     *
     * @var string
     */
    private $name = '';

    /**
     * Whether or not this name is enabled
     *
     * @var boolean
     */
    private $enabled = false;

    /**
     * The tools that belong to the programming language.
     */
    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'tool_has_programming_language');
    }
}
