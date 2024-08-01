<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DurHasTool extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'dur_id',
        'tool_id',
    ];

    /**
     * The table associated with the model
     * 
     * @var string
     */
    protected $table = 'dur_has_tools';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = false;
}
