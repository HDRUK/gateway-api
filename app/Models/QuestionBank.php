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

class QuestionBank extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;

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
    ];

    /**
     * Specifically requests that Laravel casts the tiny ints as boolean
     */
    protected $casts = [
        'locked' => 'boolean',
        'archived' => 'boolean',
        'force_required' => 'boolean',
        'allow_guidance_override' => 'boolean',
    ];

    /**
     * The question versions associated with this question.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(QuestionBankVersion::class, 'question_parent_id');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(QuestionBankVersion::class, 'question_parent_id')
            ->orderBy('version', 'desc');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMant(Team::class, 'qb_question_has_team');
    }
}
