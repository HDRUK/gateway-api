<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class ProgrammingLanguage extends Model
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
    public $table = 'programming_languages';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Represents the name of this programming language
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
     * The tools that belong to the programming language.
     */
    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'tool_has_programming_language');
    }
}
