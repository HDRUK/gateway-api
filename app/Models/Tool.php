<?php

namespace App\Models;

use App\Models\Tag;
use App\Models\Category;
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
        'mongo_object_id', 
        'mongo_id', 
        'name', 
        'url', 
        'description', 
        'license', 
        'tech_stack', 
        'category_id', 
        'user_id', 
        'enabled',
        'programming_language', 
        'programming_package', 
        'type_category', 
        'associated_authors', 
        'contact_address',
    ];

    /**
     * Specifically requests that Laravel casts the tiny ints as boolean
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Get the ids associated with the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)
            ->select('firstname', 'lastname');
    }

    /**
     * The tags that belong to the tool.
     */
    public function tag(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'tool_has_tags');
    }

    public function review()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @mixin BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class,'category_id', 'id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
