<?php

namespace App\Models;

use App\Models\Dataset;

use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    use HasFactory, SoftDeletes, Prunable;

    /**
     * The table associated with this model.
     * 
     * @var string
     */
    protected $table = 'publications';

    public $timestamps = true;

    protected $fillable = [
        'paper_title',
        'authors',
        'year_of_publication',
        'paper_doi',
        'publication_type',
        'journal_name',
        'abstract',
        'url',
    ];

    /**
     * The datasets that belong to a publication.
     */
    public function datasets(): BelongsToMany
    {
        return $this->belongsToMany(Dataset::class, 'publication_has_dataset');
    }
}
