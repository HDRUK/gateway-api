<?php

return [

    // Facet fields per type, comma-delimited for Typesense's facet_by param.
    // Must match `facet => true` in the corresponding model's typesenseCollectionSchema().
    'facet_map' => [
        'datasets'                => 'publisherName,keywords,dataType,dataSubType,geographicLocation,formatAndStandards,accessService,containsBioSamples,sampleAvailability,isCohortDiscovery,dataProviderColl,collectionNames,dataUseTitles,deliveryLeadTime,waveType,recruitmentStatus,followUpFrequency,isLongitudinal,isFamilyBased',
        'tools'                   => 'license,programmingLanguages,typeCategory',
        'collections'             => 'publisherName,datasetTitles,dataProviderColl',
        'dur'                     => 'publisherName,sector,datasetTitles,collectionNames,dataProviderColl,accessType',
        'publications'            => 'publicationType,datasetTitles,datasetLinkTypes',
        'data_custodian_networks' => 'publisherNames,datasetTitles',
        'data_custodians'         => 'datasetTitles,dataType,geographicLocation',
    ],

    // Fields callers may pass as pipe-delimited V2 filters (?publisherName=PIONEER|SAIL).
    // Only known facet fields are forwarded — keeps pagination/sort params out of filter_by.
    'filterable_map' => [
        'datasets'                => ['publisherName', 'keywords', 'dataType', 'dataSubType', 'geographicLocation', 'formatAndStandards', 'accessService', 'containsBioSamples', 'sampleAvailability', 'isCohortDiscovery', 'dataProviderColl', 'collectionNames', 'dataUseTitles', 'startDate', 'endDate', 'deliveryLeadTime', 'waveType', 'recruitmentStatus', 'followUpFrequency', 'isLongitudinal', 'isFamilyBased'],
        'tools'                   => ['license', 'programmingLanguages', 'typeCategory'],
        'collections'             => ['publisherName', 'datasetTitles', 'dataProviderColl'],
        'dur'                     => ['publisherName', 'sector', 'datasetTitles', 'collectionNames', 'dataProviderColl', 'accessType'],
        'publications'            => ['publicationType', 'datasetTitles', 'datasetLinkTypes'],
        'data_custodian_networks' => ['publisherNames', 'datasetTitles'],
        'data_custodians'         => ['datasetTitles', 'dataType', 'geographicLocation'],
    ],

    // Fields the V2 `advanced` rule tree may use, by kind (see
    // App\Services\Search\AdvancedQueryCompiler). "list" fields match exact
    // values, "number" fields a from/to range, and "overlap" fields are a
    // virtual range checked against a record's own min/max fields.
    'advanced_map' => [
        'datasets' => [
            'keywords'           => ['kind' => 'list'],
            'publisherName'      => ['kind' => 'list'],
            'dataType'           => ['kind' => 'list'],
            'dataSubType'        => ['kind' => 'list'],
            'geographicLocation' => ['kind' => 'list'],
            'formatAndStandards' => ['kind' => 'list'],
            'accessService'      => ['kind' => 'list'],
            'sampleAvailability' => ['kind' => 'list'],
            'collectionNames'    => ['kind' => 'list'],
            'deliveryLeadTime'   => ['kind' => 'list'],
            'waveType'           => ['kind' => 'list'],
            'recruitmentStatus'  => ['kind' => 'list'],
            'followUpFrequency'  => ['kind' => 'list'],
            'containsBioSamples' => ['kind' => 'boolean'],
            'isCohortDiscovery'  => ['kind' => 'boolean'],
            'isLongitudinal'     => ['kind' => 'boolean'],
            'isFamilyBased'      => ['kind' => 'boolean'],
            'populationSize'     => ['kind' => 'number'],
            'startYear'          => ['kind' => 'number'],
            'endYear'            => ['kind' => 'number'],
            'ageRange'           => ['kind' => 'overlap', 'min' => 'ageRangeMin', 'max' => 'ageRangeMax'],
        ],
    ],

    // Variable search: one document per structural metadata column, matched
    // by meaning through Typesense's built-in embedding model (downloaded by
    // Typesense itself on first collection create - no external API).
    'variables' => [
        'enabled'            => env('TYPESENSE_VARIABLES_ENABLED', true),
        'collection'         => 'dataset_variables',
        'model'              => env('TYPESENSE_VARIABLES_MODEL', 'ts/all-MiniLM-L12-v2'),
        // Cosine distance; lower is closer. 0.70 keeps "GHQ-12 total score"
        // for "mental health" but drops "employment status".
        'distance_threshold' => (float) env('TYPESENSE_VARIABLES_DISTANCE', 0.70),
        'max_terms'          => 6,
        'max_datasets'       => 50,
        'per_dataset'        => 25,
        // Typesense embeds as it imports, so a large dataset in one request
        // can outlast a proxy's timeout. Variables go in batches instead,
        // each allowed this many seconds.
        'import_batch_size'  => (int) env('TYPESENSE_VARIABLES_IMPORT_BATCH_SIZE', 100),
        'import_timeout'     => (int) env('TYPESENSE_VARIABLES_IMPORT_TIMEOUT', 120),
    ],

];
