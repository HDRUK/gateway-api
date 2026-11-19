<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Upload extends Model
{
    use HasFactory;

    public const ENTITY_TYPE_DATA_ACCESS_APPLICATION = 'dataAccessApplication';

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
        'error_details',
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

    public function scopeForDataAccessApplication(Builder $query, int $applicationId): Builder
    {
        return $query
            ->where('entity_type', self::ENTITY_TYPE_DATA_ACCESS_APPLICATION)
            ->where('entity_id', $applicationId);
    }
}
