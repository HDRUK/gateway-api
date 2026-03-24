<?php

use App\Models\Dataset;
use App\Models\ProjectGrant;
use App\Models\CancerTypeFilter;
use App\Services\CrukAuthService;
use App\Http\Resources\DatasetResource;
use App\Http\Resources\DatasetIndexResource;
use App\Http\Resources\ProjectGrantResource;
use App\Http\Resources\ProjectGrantIndexResource;
use App\Http\Resources\CancerTypeFilterResource;
use App\Http\Resources\CrukAuthResource;

/**
 * Partner context configuration.
 *
 * Controls which Resource class is used to shape API responses depending
 * on the active partner. The partner is resolved from (in priority order):
 *   1. x-partner-context request header
 *   2. DEFAULT_PARTNER_CONTEXT environment variable
 *   3. Fallback: HDRUK
 *
 * Adding a new partner
 * --------------------
 * 1. Create your resource subclass, e.g.:
 *      app/Http/Resources/PartnerXDatasetResource.php
 *
 * 2. Add the partner entry below:
 *      'PARTNER_X' => [
 *          Dataset::class => \App\Http\Resources\PartnerXDatasetResource::class,
 *      ],
 *
 * 3. In the partner's deployment, set:
 *      DEFAULT_PARTNER_CONTEXT=PARTNER_X
 *    or send the header:
 *      x-partner-context: PARTNER_X
 *
 * If a partner does not define an override for a specific model, the HDRUK
 * default for that model is used automatically.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Default partner
    |--------------------------------------------------------------------------
    */
    'default' => env('DEFAULT_PARTNER_CONTEXT', 'HDRUK'),

    /*
    |--------------------------------------------------------------------------
    | Detail (show) resource map
    |--------------------------------------------------------------------------
    | Keyed by partner identifier → model class → resource class.
    */
    'resources' => [

        'HDRUK' => [
            Dataset::class => DatasetResource::class,
            ProjectGrant::class => ProjectGrantResource::class,
            CancerTypeFilter::class => CancerTypeFilterResource::class,
            CrukAuthService::class => CrukAuthResource::class,
        ],

        'CRUK' => [
            Dataset::class => DatasetResource::class,
            ProjectGrant::class => ProjectGrantResource::class,
            CancerTypeFilter::class => CancerTypeFilterResource::class,
            CrukAuthService::class => CrukAuthResource::class,
        ],

        // 'PARTNER_X' => [
        //     Dataset::class => \App\Http\Resources\PartnerXDatasetResource::class,
        // ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Index (listing) resource map
    |--------------------------------------------------------------------------
    | Separate map for paginated list responses, which typically return a
    | lighter payload than the full detail resource.
    */
    'index_resources' => [

        'HDRUK' => [
            Dataset::class => DatasetIndexResource::class,
            ProjectGrant::class => ProjectGrantIndexResource::class,
            CancerTypeFilter::class => CancerTypeFilterResource::class,
        ],

        'CRUK' => [
            Dataset::class => DatasetIndexResource::class,
            ProjectGrant::class => ProjectGrantIndexResource::class,
            CancerTypeFilter::class => CancerTypeFilterResource::class,
        ],

        // 'PARTNER_X' => [
        //     Dataset::class => \App\Http\Resources\PartnerXDatasetIndexResource::class,
        // ],

    ],

];
