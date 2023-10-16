<?php

return [
    // Summary
    'properties/summary/title'                                              => 'summary/title',
    'properties/summary/abstract'                                           => 'summary/abstract',
    'properties/summary/publisher/contactPoint'                             => 'summary/publisher/contactPoint',
    'properties/summary/keywords'                                           => 'summary/keywords',
    'properties/summary/publisher/name'                                     => 'summary/publisher/name',
    'properties/summary/publisher/memberOf'                                 => 'summary/publisher/memberOf',
    'properties/summary/doiName'                                            => 'summary/doiName',

    // Documentation
    'properties/documentation/description'                                  => 'documentation/description',
    'properties/documentation/isPartOf'                                     => 'documentation/isPartOf',
    'properties/documentation/associatedMedia'                              => 'documentation/associatedMedia',

    // Provenance
    'properties/provenance/temporal/accrualPeriodicity'                     => 'provenance/temporal/accrualPeriodicity',
    'properties/provenance/temporal/distributionReleaseDate'                => 'provenance/temporal/distributionReleaseDate',
    'properties/provenance/origin/source'                                   => 'provenance/origin/source',
    'properties/provenance/origin/collectionSituation'                      => 'provenance/origin/collectionSituation',
    'properties/provenance/temporal/startDate'                              => 'provenance/temporal/startDate',
    'properties/provenance/origin/purpose'                                  => 'provenance/origin/purpose',
    'properties/provenance/temporal/timeLag'                                => 'provenance/temporal/timeLag',
    'properties/provenance/temporal/endDate'                                => 'provenance/temporal/endDate',

    // Coverage
    'properties/coverage/pathway'                                           => 'coverage/pathway',
    'properties/coverage/spatial'                                           => 'coverage/spatial',
    'properties/coverage/followup'                                          => 'coverage/followup',
    'properties/coverage/physicalSampleAvailability'                        => 'coverage/physicalSampleAvailability',
    'properties/coverage/typicalAgeRange'                                   => 'coverage/typicalAgeRange',

    // Accessibility
    'properties/accessibility/usage/dataUseLimitation'                      => 'accessibility/usage/dataUseLimitation',
    'properties/accessibility/access/deliveryLeadTime'                      => 'accessibility/access/deliveryLeadTime',
    'properties/accessibility/usage/investigations'                         => 'accessibility/usage/investigations',
    'properties/accessibility/access/dataProcessor'                         => 'accessibility/access/dataProcessor',
    'properties/accessibility/formatAndStandards/vocabularyEncodingScheme'  => 'accessibility/formatAndStandards/vocabularyEncodingScheme',
    'properties/accessibility/formatAndStandards/format'                    => 'accessibility/formatAndStandards/format',
    'properties/accessibility/formatAndStandards/conformsTo'                => 'accessibility/formatAndStandards/conformsTo',
    'properties/accessibility/access/dataController'                        => 'accessibility/access/dataController',
    'properties/accessibility/usage/dataUseRequirements'                    => 'accessibility/usage/dataUseRequirements',
    'properties/accessibility/usage/isReferencedBy'                         => 'accessibility/usage/isReferencedBy',
    'properties/accessibility/access/accessRights'                          => 'accessibility/access/accessRights',
    'properties/accessibility/access/jurisdiction'                          => 'accessibility/access/jurisdiction',
    'properties/accessibility/access/accessRequestCost'                     => 'accessibility/access/accessRequestCost',
    'properties/accessibility/access/accessService'                         => 'accessibility/access/accessService',
    'properties/accessibility/formatAndStandards/language'                  => 'accessibility/formatAndStandards/language',
    'properties/accessibility/usage/resourceCreator'                        => 'accessibility/usage/resourceCreator',

    // Enrichment and Linkage
    'properties/enrichmentAndLinkage/derivation'                            => 'enrichmentAndLinkage/derivation',
    'properties/enrichmentAndLinkage/qualifiedRelation'                     => 'enrichmentAndLinkage/qualifiedRelation',
    'properties/enrichmentAndLinkage/tools'                                 => 'enrichmentAndLinkage/tools',

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
