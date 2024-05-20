<?php

namespace App\Models;

use App\Models\Tag;
use App\Models\License;
use App\Models\Category;
use App\Models\Publication;
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
        'associated_authors', 
        'contact_address',
    ];

    /**
     * Specifically requests that Laravel casts the tiny ints as boolean
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)
            ->select('firstname', 'lastname');
    }

    public function tag(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'tool_has_tags');
    }

    public function review()
    {
        return $this->hasMany(Review::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class,'category_id', 'id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function programmingLanguages(): BelongsToMany
    {
        return $this->belongsToMany(ProgrammingLanguage::class, 'tool_has_programming_language');
    }

    public function programmingPackages(): BelongsToMany
    {
        return $this->belongsToMany(ProgrammingPackage::class, 'tool_has_programming_package');
    }

    public function typeCategory(): BelongsToMany
    {
        return $this->belongsToMany(TypeCategory::class, 'tool_has_type_category');
    }

    public function publications(): BelongsToMany
    {
        return $this->belongsToMany(Publication::class, 'publication_has_tools');
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class, 'license', 'id');
    }
}
