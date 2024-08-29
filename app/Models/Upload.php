<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'file_location',
        'user_id',
        'status',
        'error',
        'entity_type',
        'entity_id'
    ];

    /**
     * Table associated with this model
     *
     * @var string
     */
    public $table = 'uploads';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = true;

}
