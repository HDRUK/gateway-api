<?php

namespace App\Models;

use App\Models\Tool;
use App\Http\Enums\TagType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

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
     * Enabled for this tag
     * 
     * @var bool
     */
    private $enabled = true;

    /**
     * Specifically requests that Laravel casts the tiny ints as boolean
     */
    protected $casts = [
        'type' => TagType::class,
        'enabled' => 'boolean',
    ];

    protected $fillable = [
        'type',
        'description',
        'enabled',
    ];

    /**
     * The tools that belong to the tag.
     */
    public function tool(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'tool_has_tags');
    }
}
