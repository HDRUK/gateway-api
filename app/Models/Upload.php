<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Str;

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
        'entity_id',
        'structural_metadata',
        'question_id',
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }
}
