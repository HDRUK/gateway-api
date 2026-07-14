<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TissueSampleCollection extends Model
{
    protected $table = 'gwdm30_tissues_sample_collections';

    protected $fillable = [
        'dataset_version_id',
        'collection_id',
        'data_categories',
        'material_type',
        'access_conditions',
        'collection_type',
        'disease',
        'storage_temperature',
        'sample_age_range',
        'tsm_id',
        'tsm_sample_type',
        'tsm_storage_temperature',
        'tsm_creation_date',
        'tsm_anatomical_site_ontology_code',
        'tsm_anatomical_site_ontology_description',
        'tsm_anatomical_site_free_text',
        'tsm_sample_content_diagnosis',
        'tsm_use_restrictions',
        'tsm_donor_id',
        'tsm_donor_sex',
        'tsm_donor_birth_date',
        'tsm_donor_data_categories',
    ];

    protected $casts = [
        'data_categories' => 'array',
        'material_type' => 'array',
        'access_conditions' => 'array',
        'collection_type' => 'array',
        'disease' => 'array',
        'storage_temperature' => 'array',
        'sample_age_range' => 'array',
        'tsm_sample_type' => 'array',
        'tsm_anatomical_site_ontology_code' => 'array',
        'tsm_anatomical_site_ontology_description' => 'array',
        'tsm_anatomical_site_free_text' => 'array',
        'tsm_sample_content_diagnosis' => 'array',
        'tsm_use_restrictions' => 'array',
        'tsm_donor_data_categories' => 'array',
        'tsm_creation_date' => 'date',
        'tsm_donor_birth_date' => 'date',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
