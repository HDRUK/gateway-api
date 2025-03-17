<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class QuestionBankVersion extends Model
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

    protected $table = 'question_bank_versions';

    public $timestamps = true;

    protected $fillable = [
        'question_id',
        'version',
        'default',
        'required',
        'question_json',
    ];

    /**
     * Specifically requests that Laravel casts the tiny ints as boolean
     */
    protected $casts = [
        'required' => 'boolean',
        'default' => 'boolean',
        'question_json' => 'array',
    ];

    /**
     * The question this version is associated with.
     */
    public function question(): belongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_id');
    }

    public function childVersions(): belongsToMany
    {
        return $this->belongsToMany(
            QuestionBankVersion::class,
            'question_bank_version_has_child_version',
            'parent_qbv_id',
            'child_qbv_id'
        )->withPivot('condition');
    }

    public function parentVersion(): belongsTo
    {
        return $this->belongsTo(
            QuestionBankVersion::class,
            'question_bank_version_has_child_version',
            'child_qbv_id',
            'parent_qbv_id'
        );
    }
}
