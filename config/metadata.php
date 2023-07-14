<?php

return [
    // Summary
    'properties/summary/title'                                              => 'data/datasetv2/summary/title',
    'properties/summary/abstract'                                           => 'data/datasetv2/summary/abstract',
    'properties/summary/publisher/contactPoint'                             => 'data/datasetv2/summary/publisher/contactPoint',
    'properties/summary/keywords'                                           => 'data/datasetv2/summary/keywords',
    'properties/summary/publisher/name'                                     => 'data/datasetv2/summary/publisher/name',
    'properties/summary/publisher/memberOf'                                 => 'data/datasetv2/summary/publisher/memberOf',
    'properties/summary/doiName'                                            => 'data/datasetv2/summary/doiName',

    // Documentation
    'properties/documentation/description'                                  => 'data/datasetv2/documentation/description',
    'properties/documentation/isPartOf'                                     => 'data/datasetv2/documentation/isPartOf',
    'properties/documentation/associatedMedia'                              => 'data/datasetv2/documentation/associatedMedia',

    // Provenance
    'properties/provenance/temporal/accrualPeriodicity'                     => 'data/datasetv2/provenance/temporal/accrualPeriodicity',
    'properties/provenance/temporal/distributionReleaseDate'                => 'data/datasetv2/provenance/temporal/distributionReleaseDate',
    'properties/provenance/origin/source'                                   => 'data/datasetv2/provenance/origin/source',
    'properties/provenance/origin/collectionSituation'                      => 'data/datasetv2/provenance/origin/collectionSituation',
    'properties/provenance/temporal/startDate'                              => 'data/datasetv2/provenance/temporal/startDate',
    'properties/provenance/origin/purpose'                                  => 'data/datasetv2/provenance/origin/purpose',
    'properties/provenance/temporal/timeLag'                                => 'data/datasetv2/provenance/temporal/timeLag',
    'properties/provenance/temporal/endDate'                                => 'data/datasetv2/provenance/temporal/endDate',

    // Coverage
    'properties/coverage/pathway'                                           => 'data/datasetv2/coverage/pathway',
    'properties/coverage/spatial'                                           => 'data/datasetv2/coverage/spatial',
    'properties/coverage/followup'                                          => 'data/datasetv2/coverage/followup',
    'properties/coverage/physicalSampleAvailability'                        => 'data/datasetv2/coverage/physicalSampleAvailability',
    'properties/coverage/typicalAgeRange'                                   => 'data/datasetv2/coverage/typicalAgeRange',

    // Accessibility
    'properties/accessibility/usage/dataUseLimitation'                      => 'data/datasetv2/accessibility/usage/dataUseLimitation',
    'properties/accessibility/access/deliveryLeadTime'                      => 'data/datasetv2/accessibility/access/deliveryLeadTime',
    'properties/accessibility/usage/investigations'                         => 'data/datasetv2/accessibility/usage/investigations',
    'properties/accessibility/access/dataProcessor'                         => 'data/datasetv2/accessibility/access/dataProcessor',
    'properties/accessibility/formatAndStandards/vocabularyEncodingScheme'  => 'data/datasetv2/accessibility/formatAndStandards/vocabularyEncodingScheme',
    'properties/accessibility/formatAndStandards/format'                    => 'data/datasetv2/accessibility/formatAndStandards/format',
    'properties/accessibility/formatAndStandards/conformsTo'                => 'data/datasetv2/accessibility/formatAndStandards/conformsTo',
    'properties/accessibility/access/dataController'                        => 'data/datasetv2/accessibility/access/dataController',
    'properties/accessibility/usage/dataUseRequirements'                    => 'data/datasetv2/accessibility/usage/dataUseRequirements',
    'properties/accessibility/usage/isReferencedBy'                         => 'data/datasetv2/accessibility/usage/isReferencedBy',
    'properties/accessibility/access/accessRights'                          => 'data/datasetv2/accessibility/access/accessRights',
    'properties/accessibility/access/jurisdiction'                          => 'data/datasetv2/accessibility/access/jurisdiction',
    'properties/accessibility/access/accessRequestCost'                     => 'data/datasetv2/accessibility/access/accessRequestCost',
    'properties/accessibility/access/accessService'                         => 'data/datasetv2/accessibility/access/accessService',
    'properties/accessibility/formatAndStandards/language'                  => 'data/datasetv2/accessibility/formatAndStandards/language',
    'properties/accessibility/usage/resourceCreator'                        => 'data/datasetv2/accessibility/usage/resourceCreator',

    // Enrichment and Linkage
    'properties/enrichmentAndLinkage/derivation'                            => 'data/datasetv2/enrichmentAndLinkage/derivation',
    'properties/enrichmentAndLinkage/qualifiedRelation'                     => 'data/datasetv2/enrichmentAndLinkage/qualifiedRelation',
    'properties/enrichmentAndLinkage/tools'                                 => 'data/datasetv2/enrichmentAndLinkage/tools',

    // Observations
    //'properties/summary/observations' => 'data/datasetv2/observations',
    /*
            TODO - Investigate this. Removed for now as it causes this exception:

            array:7 [ // app/Console/Commands/MauroConduit.php:47
                "status" => 422
                "reason" => "Unprocessable Entity"
                "errorCode" => "UEX--"
                "message" => "Validation error whilst flushing entity [uk.ac.ox.softeng.maurodatamapper.core.facet.Metadata]:"
                "path" => "/mauro/api/folders/0930397d-6f49-4f56-b41f-499da24e35b8/dataModels"
                "version" => "5.3.0"
                "validationErrors" => array:2 [
                    "total" => 1
                    "errors" => array:1 [
                        0 => array:1 [
                            "message" => "No converter found capable of converting from type [org.apache.groovy.json.internal.LazyMap] to type [java.lang.String]"
                        ]
                    ]
                ]
            ]
        */
];
