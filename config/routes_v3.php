<?php

return [
    // v3 datasets — delta versioning update
    [
        'name'                => 'datasets',
        'method'              => 'put',
        'path'                => '/datasets/{id}',
        'methodController'    => 'DatasetController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V3',
        'middleware'          => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,datasets.update',
        ],
        'constraint' => [
            'id', '[0-9]+'
        ],
    ],

    // v3 dataset version history — public endpoints (no JWT), consistent with showActive
    [
        'name'                => 'datasets',
        'method'              => 'get',
        'path'                => '/datasets/{id}/versions',
        'methodController'    => 'DatasetController@listVersions',
        'namespaceController' => 'App\Http\Controllers\Api\V3',
        'middleware'          => [
            'sanitize.input',
        ],
        'constraint' => [
            'id', '[0-9]+'
        ],
    ],
    [
        'name'                => 'datasets',
        'method'              => 'get',
        'path'                => '/datasets/{id}/version/{version}',
        'methodController'    => 'DatasetController@showVersion',
        'namespaceController' => 'App\Http\Controllers\Api\V3',
        'middleware'          => [
            'sanitize.input',
        ],
        'constraint' => [
            'id', '[0-9]+'
        ],
    ],

    // v3 team datasets — delta versioning update
    [
        'name'                => 'datasets',
        'method'              => 'put',
        'path'                => '/teams/{teamId}/datasets/{id}',
        'methodController'    => 'TeamDatasetController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V3',
        'middleware'          => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,datasets.update',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],

    // dasboard endpoints for data custodian/team

    [
        'name'                => 'datasets.count',
        'method'              => 'get',
        'path'                => '/teams/{id}/dashboard/{entity}/count',
        'methodController'    => 'TeamDashboardController@entityCount',
        'namespaceController' => 'App\Http\Controllers\Api\V3',
        'middleware'          => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'entity' => 'datasets|datauses|tools|collections|publications',
        ],
    ],
    [
        'name'                => 'datasets.views.360',
        'method'              => 'get',
        'path'                => '/teams/{id}/dashboard/datasets/views/360',
        'methodController'    => 'TeamDashboardController@datasetViews360',
        'namespaceController' => 'App\Http\Controllers\Api\V3',
        'middleware'          => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name'                => 'datasets.views.top',
        'method'              => 'get',
        'path'                => '/teams/{id}/dashboard/datasets/views/top',
        'methodController'    => 'TeamDashboardController@datasetViewsTop',
        'namespaceController' => 'App\Http\Controllers\Api\V3',
        'middleware'          => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name'                => 'collections.views',
        'method'              => 'get',
        'path'                => '/teams/{id}/dashboard/collections/views',
        'methodController'    => 'TeamDashboardController@collectionViews',
        'namespaceController' => 'App\Http\Controllers\Api\V3',
        'middleware'          => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name'                => 'datacustodians.views',
        'method'              => 'get',
        'path'                => '/teams/{id}/dashboard/datacustodians/views',
        'methodController'    => 'TeamDashboardController@datacustodianViews',
        'namespaceController' => 'App\Http\Controllers\Api\V3',
        'middleware'          => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

];
