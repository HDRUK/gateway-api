<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class DataAccessApplicationReview extends Model
{
    use HasFactory;
    use Notifiable;
    use Prunable;

    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'dar_application_reviews';

    protected $fillable = [
        'application_id',
        'question_id',
        'resolved',
    ];

    public function comments(): HasMany
    {
        return $this->hasMany(DataAccessApplicationComment::class, 'review_id');
    }
}
