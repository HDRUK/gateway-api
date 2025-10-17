<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Widget extends Model
{
    use HasFactory;
    use SoftDeletes;

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
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
