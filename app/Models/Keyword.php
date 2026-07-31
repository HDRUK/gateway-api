<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *   schema="Keyword",
 *   description="A keyword record used to tag datasets, tools, collections, DURs and publications",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="name", type="string", example="genomics"),
 *   @OA\Property(property="enabled", type="boolean", example=true),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-01T08:00:00Z"),
 * )
 */
class Keyword extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'enabled',
    ];

    /**
     * Table associated with this model
     *
     * @var string
     */
    protected $table = 'keywords';

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
}
