<?php

namespace App\Models\Gwdm30;

use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Accessibility extends Model
{
    protected $table = 'gwdm30_accessibility';

    protected $fillable = [
        'dataset_version_id',
        'access_rights',
        'access_service',
        'access_service_category',
        'access_request_cost',
        'delivery_lead_time',
        'jurisdiction',
        'data_controller',
        'data_processor',
        'legal_basis',
        'personal_data',
        'applicable_legislation',
        'resource_creator',
        'vocabulary_encoding_schemes',
        'conforms_to',
        'languages',
        'data_use_limitation',
        'data_use_requirements',
        'formats',
    ];

    protected $casts = [
        'data_use_limitation' => 'array',
        'data_use_requirements' => 'array',
        'formats' => 'array',
    ];

    public function datasetVersion(): BelongsTo
    {
        return $this->belongsTo(DatasetVersion::class);
    }
}
