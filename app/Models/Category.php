<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *   schema="Category",
 *   description="A tool category record",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="name", type="string", example="Data Analysis"),
 *   @OA\Property(property="enabled", type="boolean", example=true),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-01T08:00:00Z"),
 * )
 */
class Category extends Model
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
    public $table = 'categories';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Represents the name of this category
     *
     * @var string
     */
    private $name = '';

    /**
     * Whether or not this category is enabled
     *
     * @var boolean
     */
    private $enabled = false;
}
