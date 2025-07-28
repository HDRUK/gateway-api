<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataAccessApplicationHasAnswerFile extends Model
{
    use HasFactory;
    use Notifiable;
    use Prunable;

    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'dar_application_has_answer_files';

    public $timestamps = false;

    protected $fillable = [
        'application_file_id',
        'application_id',
        'upload_id',
    ];
}
