<?php

return [

    // Facet fields per type, comma-delimited for Typesense's facet_by param.
    // Must match `facet => true` in the corresponding model's typesenseCollectionSchema().
    'facet_map' => [
        'datasets'                => 'publisherName,keywords,dataType,dataSubType,geographicLocation,formatAndStandards,accessService,containsBioSamples,sampleAvailability,isCohortDiscovery',
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
        'datasets'                => ['publisherName', 'keywords', 'dataType', 'dataSubType', 'geographicLocation', 'formatAndStandards', 'accessService', 'containsBioSamples', 'sampleAvailability', 'isCohortDiscovery'],
        'tools'                   => ['license', 'programmingLanguages', 'typeCategory'],
        'collections'             => ['publisherName', 'datasetTitles', 'dataProviderColl'],
        'dur'                     => ['publisherName', 'sector', 'datasetTitles', 'collectionNames', 'dataProviderColl', 'accessType'],
        'publications'            => ['publicationType', 'datasetTitles', 'datasetLinkTypes'],
        'data_custodian_networks' => ['publisherNames', 'datasetTitles'],
        'data_custodians'         => ['datasetTitles', 'dataType', 'geographicLocation'],
    ],

];
