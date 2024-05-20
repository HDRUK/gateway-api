<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DataAccessApplicationAnswer extends Model
{
    use HasFactory, Notifiable, Prunable;

    /**
     * The table associated with the model
     * 
     * @var string
     */
    protected $table = 'dar_application_answers';

    protected $fillable = [
        'question_id',
        'application_id',
        'answer',
        'contributor_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contributor_id');
    }
}
