<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    /**
     * The question this version is associated with.
     */
    public function question(): belongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_id');
    }

}
