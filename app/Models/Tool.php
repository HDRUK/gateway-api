<?php

namespace App\Models;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tool extends Model
{
    use HasFactory, Notifiable, SoftDeletes, Prunable;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'tools';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = true;

    protected $fillable = [
        'mongo_object_id', 'name', 'url', 'description', 'license', 'tech_stack', 'user_id', 'enabled',
    ];

    /**
     * Get the ids associated with the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The tags that belong to the tool.
     */
    public function tag(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'tool_has_tags');
    }
}
