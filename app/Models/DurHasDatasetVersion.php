<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Observers\DurHasDatasetVersionObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([DurHasDatasetVersionObserver::class])]
class DurHasDatasetVersion extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;

    protected $fillable = [
        'dur_id',
        'dataset_version_id',
        'user_id',
        'application_id',
        'is_locked',
        'reason',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'dur_has_dataset_version';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = false;
}
