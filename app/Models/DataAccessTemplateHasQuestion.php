<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class DataAccessTemplateHasQuestion extends Model
{
    use HasFactory;
    use Notifiable;
    use Prunable;

    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'dar_template_has_questions';

    public $timestamps = false;

    protected $fillable = [
        'template_id',
        'question_id',
        'guidance',
        'required',
        'order'
    ];

}
