<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Summary extends Model
{
    protected $table = 'gwdm30_summary';

    protected $fillable = [
        'dataset_version_id',
        'abstract',
        'contact_point',
        'keywords',
        'controlled_keywords',
        'dataset_type',
        'dataset_sub_type',
        'in_pipeline',
        'funders',
        'dataset_aliases',
        'description',
        'doi_name',
        'license_url',
        'landing_page',
        'creator_name',
        'creator_ror_id',
        'creator_orcid_id',
        'creator_gateway_id',
        'theme',
        'publisher_name',
        'publisher_gateway_id',
        'publisher_ror_id',
        'population_size',
    ];

    protected $casts = [
        'keywords' => 'array',
        'controlled_keywords' => 'array',
        'dataset_type' => 'array',
        'dataset_sub_type' => 'array',
        'funders' => 'array',
        'dataset_aliases' => 'array',
        'theme' => 'array',
        'population_size' => 'integer',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
