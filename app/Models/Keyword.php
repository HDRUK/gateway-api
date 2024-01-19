<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'enabled',
    ];

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'keywords';

    /**
     * Specifically requests that Laravel casts the tiny ints as boolean
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Indicates if this model is timestamped
     * 
     * @var bool
     */
    public $timestamps = true;
}
