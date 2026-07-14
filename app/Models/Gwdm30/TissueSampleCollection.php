<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int|null $dataset_version_id
 * @property string|null $collection_id
 * @property array|null $data_categories
 * @property array|null $material_type
 * @property array|null $access_conditions
 * @property array|null $collection_type
 * @property array|null $disease
 * @property array|null $storage_temperature
 * @property array|null $sample_age_range
 * @property string|null $tsm_id
 * @property array|null $tsm_sample_type
 * @property string|null $tsm_storage_temperature
 * @property \Illuminate\Support\Carbon|null $tsm_creation_date
 * @property array|null $tsm_anatomical_site_ontology_code
 * @property array|null $tsm_anatomical_site_ontology_description
 * @property array|null $tsm_anatomical_site_free_text
 * @property array|null $tsm_sample_content_diagnosis
 * @property array|null $tsm_use_restrictions
 * @property string|null $tsm_donor_id
 * @property string|null $tsm_donor_sex
 * @property \Illuminate\Support\Carbon|null $tsm_donor_birth_date
 * @property array|null $tsm_donor_data_categories
 * @property-read \Illuminate\Support\Carbon|null $created_at
 * @property-read \Illuminate\Support\Carbon|null $updated_at
 */
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
