<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CollectionHasKeyword extends Model
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Prunable;


    protected $fillable = [
        'collection_id',
        'keyword_id',
    ];

    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'collection_has_keywords';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = false;
}
