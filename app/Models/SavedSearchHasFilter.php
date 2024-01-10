<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedSearchHasFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'saved_search_id', 'filter_id',
    ];

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'saved_search_has_filters';

    /**
     * Indicates if the model should be timestamped
     * 
     * @var bool
     */
    public $timestamps = false;
}
