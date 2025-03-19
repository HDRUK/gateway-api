<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataAccessTemplateHasFile extends Model
{
    use HasFactory;
    use Notifiable;
    use Prunable;

    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'dar_template_has_files';

    public $timestamps = false;

    protected $fillable = [
        'template_id',
        'upload_id'
    ];

}
