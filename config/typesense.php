<?php

return [

    // Facet fields per type, comma-delimited for Typesense's facet_by param.
    // Must match `facet => true` in the corresponding model's typesenseCollectionSchema().
    'facet_map' => [
        'datasets'                => 'publisherName,keywords,dataType,dataSubType,geographicLocation,conformsTo,accessService,containsBioSamples,sampleAvailability,isCohortDiscovery',
        'tools'                   => 'license,programmingLanguages,typeCategory',
        'collections'             => '',
        'dur'                     => '',
        'publications'            => 'publication_type',
        'data_custodian_networks' => 'publisherNames,datasetTitles',
    ],

    // Fields callers may pass as pipe-delimited V2 filters (?publisherName=PIONEER|SAIL).
    // Only known facet fields are forwarded — keeps pagination/sort params out of filter_by.
    'filterable_map' => [
        'datasets'                => ['publisherName', 'keywords', 'dataType', 'dataSubType', 'geographicLocation', 'conformsTo', 'accessService', 'containsBioSamples', 'sampleAvailability', 'isCohortDiscovery'],
        'tools'                   => ['license', 'programmingLanguages', 'typeCategory'],
        'publications'            => ['publication_type'],
        'data_custodian_networks' => ['publisherNames', 'datasetTitles'],
    ],

];
