<?php

namespace App\Models;

use App\Http\Enums\TagType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tag extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'tags';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = true;

    /**
     * Description for this tag
     * 
     * @var string
     */
    private $description = '';

    protected $casts = [
        'type' => TagType::class,
    ];

    protected $fillable = [
        'type',
        'description',
        'enabled',
    ];
}
