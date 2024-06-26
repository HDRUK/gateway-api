<?php

namespace App\Models;

use App\Models\Dataset;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class Library extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

    protected $fillable = [
        'user_id',
        'dataset_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $table = 'library';

    public $timestamps = true;

    /**
     * Relationship to Dataset model.
     */
    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class, 'dataset_id', 'id');
    }
}
