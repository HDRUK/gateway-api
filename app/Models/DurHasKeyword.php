<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DurHasKeyword extends Model
{
    use HasFactory;

    protected $fillable = [
        'dur_id',
        'keyword_id',
    ];

    /**
     * The table associated with the model
     * 
     * @var string
     */
    protected $table = 'dur_has_keywords';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = false;
}
