<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CollectionHasDatasetVersion extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;

    protected $fillable = [
        'collection_id',
        'dataset_version_id',
        'user_id',
        'application_id',
        'reason',
        'created_at',
        'updated_at',
    ];

    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'collection_has_dataset_version';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = false;
}
