<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuestionBankVersionHasChildVersion extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;

    public $timestamps = false;

    protected $fillable = [
        'parent_qbv_id', 'child_qbv_id', 'condition'
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'question_bank_version_has_child_version';

}
