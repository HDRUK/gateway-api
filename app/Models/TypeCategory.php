<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TypeCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
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
    public $table = 'type_categories';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = true;

    /**
     * Represents the name of this tool type category
     * 
     * @var string
     */
    private $name = '';

    /**
     * Represents the description of this tool type category
     * 
     * @var string
     */
    private $description = '';

    /**
     * Whether or not this name is enabled
     * 
     * @var boolean
     */
    private $enabled = false;

    /**
     * The tools that belong to the type category.
     */
    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'tool_has_type_category');
    }
}
