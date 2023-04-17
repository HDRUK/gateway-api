<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Feature extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

    /**
     * Table associated with this model
     * 
     * @var string
     */
    protected $table = 'features';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = true;

    /**
     * Enabled for this tag
     * 
     * @var bool
     */
    private $enabled = true;

    /**
     * Feature name
     * 
     * @var string
     */
    private $name = '';


    protected $fillable = [
        'name',
        'enabled',
    ];
}
