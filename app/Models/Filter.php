<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Filter extends Model
{
    use HasFactory;

    protected $fillable = [
        'updated_at',
        'deleted_at',
        'type',
        'value',
        'keys',
        'enabled',
    ];

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'filters';

    /**
     * Indicates if this model is timestamped
     * 
     * @var bool
     */
    public $timestamps = true;

    /**
     * Represents the filter type this filter is linked
     * to
     * 
     * @var string
     */
    private $type = '';

    /**
     * Represents the filter value
     * 
     * @var string
     */
    private $value = '';

    /**
     * Represents the filter key this filter is linked
     * to
     * 
     * @var string
     */
    private $keys = '';

    /**
     * Indicates whether this model is enabled or disabled
     * 
     * @var bool
     */
    private $enabled = false;

}
