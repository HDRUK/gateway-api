<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataAccessApplicationComment extends Model
{
    use HasFactory;
    use Notifiable;
    use Prunable;

    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'dar_application_comments';

    protected $fillable = [
        'review_id',
        'user_id',
        'team_id',
        'comment',
    ];
}
