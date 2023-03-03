<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tags extends Model
{
    use HasFactory;

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

    /**
     * Type for this tag
     * 
     * @var string
     */
    private $type = '';
}
