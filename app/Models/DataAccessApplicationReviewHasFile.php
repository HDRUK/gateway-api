<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataAccessApplicationReviewHasFile extends Model
{
    use HasFactory;
    use Notifiable;
    use Prunable;

    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'dar_application_review_has_files';

    public $timestamps = false;

    protected $fillable = [
        'review_id',
        'upload_id'
    ];
}
