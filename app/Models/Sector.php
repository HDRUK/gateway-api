<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    use HasFactory;
 
    protected $fillable = [
        'name',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Table associated with this model
     * 
     * @var string
     */
    public $table = 'sectors';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = true;

    /**
     * Represents the name of this sector
     * 
     * @var string
     */
    private $name = '';

    /**
     * Whether or not this sector is enabled
     * 
     * @var boolean
     */
    private $enabled = false;
}
