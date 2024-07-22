<?php

namespace App\Models;

use App\Models\Tool;
use App\Models\Dataset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Publication extends Model
{
    use HasFactory, SoftDeletes, Prunable;


    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_ARCHIVED = 'ARCHIVED';

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
        'publication_type_mk1',
        'journal_name',
        'abstract',
        'url',
        'mongo_id',
        'owner_id',
        'status'
    ];

    /**
     * The datasets that belong to a publication.
     */
    public function datasets(): BelongsToMany
    {
        return $this->belongsToMany(Dataset::class, 'publication_has_dataset');
    }

    /**
     * The tools that belong to a publication.
     */
    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class, 'publication_has_tools');
    }
}
