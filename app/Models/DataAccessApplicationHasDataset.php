<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class DataAccessApplicationHasDataset extends Model
{
    use HasFactory;
    use Notifiable;
    use Prunable;

    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'dar_application_has_dataset';

    public $timestamps = false;

    protected $fillable = [
        'dar_application_id',
        'dataset_id',
    ];
}
