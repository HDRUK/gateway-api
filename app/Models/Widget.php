<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Widget extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'widgets';

    protected $fillable = [
        'team_id',
        'data_custodian_entities_ids',
        'included_datasets',
        'included_data_uses',
        'included_scripts',
        'included_collections',
        'include_search_bar',
        'include_cohort_link',
        'size_width',
        'size_height',
        'unit',
        'keep_proportions',
        'widget_name',
        'permitted_domains',
        'colours',
    ];

    protected $casts = [
        'colours' => 'array', // or 'json'
        'include_search_bar' => 'boolean',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
