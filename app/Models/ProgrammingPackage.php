<?php

namespace App\Models;

use App\Models\Tool;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgrammingPackage extends Model
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
    public $table = 'programming_packages';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = true;

    /**
     * Represents the name of this programming package
     * 
     * @var string
     */
    private $name = '';

    /**
     * Whether or not this name is enabled
     * 
     * @var boolean
     */
    private $enabled = false;

    /**
     * The tools that belong to the programming package.
     */
    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'tool_has_programming_package');
    }
}
