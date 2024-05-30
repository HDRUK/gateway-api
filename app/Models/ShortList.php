<?php

namespace App\Models;

use App\Models\Dataset;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Notifications\Notifiable;

class ShortList extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

    protected $fillable = [
        'user_id',
        'dataset_id',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'short_lists';

    /**
     * Indicates if this model is timestamped
     * 
     * @var bool
     */
    public $timestamps = true;


    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class,'dataset_id', 'id');
    }
}
