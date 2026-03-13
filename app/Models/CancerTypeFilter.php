<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CancerTypeFilter extends Model
{
    protected $table = 'cancer_type_filters';

    protected $fillable = [
        'filter_id',
        'label',
        'category',
        'primary_group',
        'count',
        'parent_id',
        'level',
        'sort_order',
    ];

    protected $casts = [
        'level' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the parent filter
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(CancerTypeFilter::class, 'parent_id');
    }

    /**
     * Get the child filters
     */
    public function children(): HasMany
    {
        return $this->hasMany(CancerTypeFilter::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get all descendants recursively
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }
}
