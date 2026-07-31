<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *   schema="Widget",
 *   description="A widget record managed by the Gateway",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="team_id", type="integer", example=7),
 *   @OA\Property(property="data_custodian_entities_ids", type="string", nullable=true, example="[1,2,3]"),
 *   @OA\Property(property="included_datasets", type="string", nullable=true, example="[10,11]"),
 *   @OA\Property(property="included_data_uses", type="string", nullable=true, example="[5]"),
 *   @OA\Property(property="included_scripts", type="string", nullable=true, example="[2]"),
 *   @OA\Property(property="included_collections", type="string", nullable=true, example="[8]"),
 *   @OA\Property(property="include_search_bar", type="boolean", example=false),
 *   @OA\Property(property="include_cohort_link", type="boolean", example=false),
 *   @OA\Property(property="size_width", type="integer", nullable=true, example=400),
 *   @OA\Property(property="size_height", type="integer", nullable=true, example=300),
 *   @OA\Property(
 *     property="unit",
 *     type="string",
 *     enum={"px","%","rem"},
 *     example="px"
 *   ),
 *   @OA\Property(property="keep_proportions", type="boolean", example=false),
 *   @OA\Property(property="widget_name", type="string", example="My widget"),
 *   @OA\Property(property="permitted_domains", type="string", nullable=true, example="example.com"),
 *   @OA\Property(property="branding_primary", type="string", nullable=true, example="#123456"),
 *   @OA\Property(property="branding_secondary", type="string", nullable=true, example="#abcdef"),
 *   @OA\Property(property="branding_neutral", type="string", nullable=true, example="#ffffff"),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-01T08:00:00Z"),
 * )
 */
class Widget extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'widgets';

    protected $fillable = [
        'team_id',
        'data_custodian_entities_ids',
        'included_datasets',
        'included_data_uses',
        'included_scripts',
        'included_collections',
        'include_search_bar',
        'include_cohort_link',
        'size_width',
        'size_height',
        'unit',
        'keep_proportions',
        'widget_name',
        'permitted_domains',
        'branding_primary',
        'branding_secondary',
        'branding_neutral',
    ];

    protected $casts = [
        'include_search_bar' => 'boolean',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
