<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionBank extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

    /**
     * The table associated with the model
     * 
     * @var string
     */

     protected $table = 'question_banks';

     protected $fillable = [
        'section_id',
        'user_id',
        'team_id',
        'default',
        'locked',
        'required',
        'question_json',
     ];

     public function user(): BelongsTo
     {
         return $this->belongsTo(User::class);
     }
 
     public function team(): BelongsTo
     {
         return $this->belongsTo(Team::class);
     }
}
