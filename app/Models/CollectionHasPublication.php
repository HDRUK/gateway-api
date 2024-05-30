<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CollectionHasPublication extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

    protected $fillable = [
        'collection_id',
        'publication_id',
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
    protected $table = 'collection_has_publications';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = false;
}
