<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @OA\Schema(
 *   schema="QuestionBank",
 *   description="A question bank question record managed by the Gateway",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="section_id", type="integer", example=3),
 *   @OA\Property(property="user_id", type="integer", example=42),
 *   @OA\Property(property="locked", type="boolean", example=false),
 *   @OA\Property(property="archived", type="boolean", example=false),
 *   @OA\Property(property="archived_date", type="string", format="date-time", nullable=true, example="2024-06-01T08:00:00Z"),
 *   @OA\Property(property="force_required", type="boolean", example=false),
 *   @OA\Property(property="allow_guidance_override", type="boolean", example=false),
 *   @OA\Property(property="is_child", type="boolean", example=false),
 *   @OA\Property(
 *     property="question_type",
 *     type="string",
 *     enum={"STANDARD","CUSTOM"},
 *     example="STANDARD"
 *   ),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-01T08:00:00Z"),
 * )
 */
class QuestionBank extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;

    public const STANDARD_TYPE = 'STANDARD';
    public const CUSTOM_TYPE = 'CUSTOM';

    /**
     * The table associated with the model
     *
     * @var string
     */

    protected $table = 'question_bank_questions';

    public $timestamps = true;

    protected $fillable = [
        'section_id',
        'user_id',
        'locked',
        'archived',
        'archived_date',
        'force_required',
        'allow_guidance_override',
        'is_child',
        'question_type',
    ];

    /**
     * Specifically requests that Laravel casts the tiny ints as boolean
     */
    protected $casts = [
        'locked' => 'boolean',
        'archived' => 'boolean',
        'force_required' => 'boolean',
        'allow_guidance_override' => 'boolean',
        'default' => 'boolean',
    ];

    /**
     * @return HasMany<QuestionBankVersion, $this>
     *
     * The question versions associated with this question.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(QuestionBankVersion::class, 'question_id');
    }

    /** @return HasOne<QuestionBankVersion, $this> */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(QuestionBankVersion::class, 'question_id')
            ->orderBy('version', 'desc');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'qb_question_has_team', 'qb_question_id', 'team_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(DataAccessSection::class);
    }
}
