<?php

return [
    // Required
    'properties/required/gatewayId'                                         => 'metadata/required/gatewayId',
    'properties/required/gatewayPid'                                        => 'metadata/required/gatewayPid',
    'properties/required/issued'                                            => 'metadata/required/issued',
    'properties/required/modified'                                          => 'metadata/required/modified',
    'properties/required/revisions'                                         => 'metadata/required/revisions',

    // Summary
    'properties/summary/title'                                              => 'metadata/summary/title',
    'properties/summary/shortTitle'                                         => 'metadata/summary/shortTitle',
    'properties/summary/abstract'                                           => 'metadata/summary/abstract',
    'properties/summary/publisher/contactPoint'                             => 'metadata/summary/contactPoint',
    'properties/summary/keywords'                                           => 'metadata/summary/keywords',
    'properties/summary/controlledKeywords'                                 => 'metadata/summary/controlledKeywords',
    'properties/summary/datasetType'                                        => 'metadata/summary/datasetType',
    'properties/summary/description'                                        => 'metadata/summary/description',
    'properties/summary/publisher/publisherName'                            => 'metadata/summary/publisher/publisherName',
    'properties/summary/doiName'                                            => 'metadata/summary/doiName',

    // Documentation
    'properties/documentation/description'                                  => 'metadata/documentation/description',
    'properties/documentation/isPartOf'                                     => 'metadata/documentation/isPartOf',
    'properties/documentation/associatedMedia'                              => 'metadata/documentation/associatedMedia',

    // Provenance
    'properties/provenance/temporal/accrualPeriodicity'                     => 'metadata/provenance/temporal/accrualPeriodicity',
    'properties/provenance/temporal/distributionReleaseDate'                => 'metadata/provenance/temporal/distributionReleaseDate',
    'properties/provenance/origin/source'                                   => 'metadata/provenance/origin/source',
    'properties/provenance/origin/collectionSituation'                      => 'metadata/provenance/origin/collectionSituation',
    'properties/provenance/temporal/startDate'                              => 'metadata/provenance/temporal/startDate',
    'properties/provenance/origin/purpose'                                  => 'metadata/provenance/origin/purpose',
    'properties/provenance/temporal/timeLag'                                => 'metadata/provenance/temporal/timeLag',
    'properties/provenance/temporal/endDate'                                => 'metadata/provenance/temporal/endDate',

    // Coverage
    'properties/coverage/pathway'                                           => 'metadata/coverage/pathway',
    'properties/coverage/spatial'                                           => 'metadata/coverage/spatial',
    'properties/coverage/followup'                                          => 'metadata/coverage/followup',
    'properties/coverage/physicalSampleAvailability'                        => 'metadata/coverage/physicalSampleAvailability',
    'properties/coverage/typicalAgeRange'                                   => 'metadata/coverage/typicalAgeRange',

    // Accessibility
    'properties/accessibility/usage/dataUseLimitation'                      => 'metadata/accessibility/usage/dataUseLimitation',
    'properties/accessibility/access/deliveryLeadTime'                      => 'metadata/accessibility/access/deliveryLeadTime',
    'properties/accessibility/usage/investigations'                         => 'metadata/accessibility/usage/investigations',
    'properties/accessibility/access/dataProcessor'                         => 'metadata/accessibility/access/dataProcessor',
    'properties/accessibility/formatAndStandards/vocabularyEncodingScheme'  => 'metadata/accessibility/formatAndStandards/vocabularyEncodingScheme',
    'properties/accessibility/formatAndStandards/format'                    => 'metadata/accessibility/formatAndStandards/format',
    'properties/accessibility/formatAndStandards/conformsTo'                => 'metadata/accessibility/formatAndStandards/conformsTo',
    'properties/accessibility/access/dataController'                        => 'metadata/accessibility/access/dataController',
    'properties/accessibility/usage/dataUseRequirements'                    => 'metadata/accessibility/usage/dataUseRequirements',
    'properties/accessibility/usage/isReferencedBy'                         => 'metadata/accessibility/usage/isReferencedBy',
    'properties/accessibility/access/accessRights'                          => 'metadata/accessibility/access/accessRights',
    'properties/accessibility/access/jurisdiction'                          => 'metadata/accessibility/access/jurisdiction',
    'properties/accessibility/access/accessRequestCost'                     => 'metadata/accessibility/access/accessRequestCost',
    'properties/accessibility/access/accessService'                         => 'metadata/accessibility/access/accessService',
    'properties/accessibility/formatAndStandards/language'                  => 'metadata/accessibility/formatAndStandards/language',
    'properties/accessibility/usage/resourceCreator'                        => 'metadata/accessibility/usage/resourceCreator',

    // Enrichment and Linkage
    'properties/enrichmentAndLinkage/derivation'                            => 'metadata/enrichmentAndLinkage/derivation',
    'properties/enrichmentAndLinkage/qualifiedRelation'                     => 'metadata/enrichmentAndLinkage/qualifiedRelation',
    'properties/enrichmentAndLinkage/tools'                                 => 'metadata/enrichmentAndLinkage/tools',

    // Observations
    //'properties/summary/observations' => 'observations',
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
