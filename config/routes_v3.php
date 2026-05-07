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
        'name'                => 'dashboard.count',
        'method'              => 'get',
        'path'                => '/teams/{id}/dashboard/{entity}/count',
        'methodController'    => 'TeamDashboardController@entityCount',
        'namespaceController' => 'App\Http\Controllers\Api\V3',
        'middleware'          => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'entity' => 'datasets|datauses|tools|collections|publications|general-enquires|fesability-enquires|data-access-requests',
        ],
    ],
    [
        'name'                => 'dashboard.datasets.views.360',
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
        'name'                => 'dashboard.datasets.views.top',
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
        'name'                => 'dashboard.collections.views',
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
        'name'                => 'dashboard.datacustodians.views',
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
    [
        'name'                => 'dashboard.download.csv',
        'method'              => 'get',
        'path'                => '/teams/{id}/dashboard/download/csv',
        'methodController'    => 'TeamDashboardController@downloadCsv',
        'namespaceController' => 'App\Http\Controllers\Api\V3',
        'middleware'          => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // data access dashboard
    [
        'name'                => 'dar.dashboard.my.applications',
        'method'              => 'get',
        'path'                => '/teams/{id}/dar/dashboard/count',
        'methodController'    => 'DataAccessDashboardController@getMyApplications',
        'namespaceController' => 'App\Http\Controllers\Api\V3',
        'middleware'          => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name'                => 'dar.dashboard.status.applications',
        'method'              => 'get',
        'path'                => '/teams/{id}/dar/dashboard/status',
        'methodController'    => 'DataAccessDashboardController@getApplicationStatus',
        'namespaceController' => 'App\Http\Controllers\Api\V3',
        'middleware'          => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name'                => 'dar.dashboard.average.time.applications',
        'method'              => 'get',
        'path'                => '/teams/{id}/dar/dashboard/average-time',
        'methodController'    => 'DataAccessDashboardController@getAverageTimeToApproval',
        'namespaceController' => 'App\Http\Controllers\Api\V3',
        'middleware'          => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name'                => 'dar.dashboard.required.actions.applications',
        'method'              => 'get',
        'path'                => '/teams/{id}/dar/dashboard/required-actions',
        'methodController'    => 'DataAccessDashboardController@getRequiredActions',
        'namespaceController' => 'App\Http\Controllers\Api\V3',
        'middleware'          => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name'                => 'dar.dashboard.timeline.applications',
        'method'              => 'get',
        'path'                => '/teams/{id}/dar/dashboard/timeline',
        'methodController'    => 'DataAccessDashboardController@getApplicationTimeline',
        'namespaceController' => 'App\Http\Controllers\Api\V3',
        'middleware'          => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // export
    // full dashboard
    [
        'name'                => 'dar.dashboard.export.csv',
        'method'              => 'get',
        'path'                => '/teams/{id}/dar/dashboard/export/csv',
        'methodController'    => 'DataAccessDashboardController@exportDashboardCsv',
        'namespaceController' => 'App\Http\Controllers\Api\V3',
        'middleware'          => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    // application timeline
    [
        'name'                => 'dar.dashboard.export.csv',
        'method'              => 'get',
        'path'                => '/teams/{id}/dar/dashboard/timeline/export/csv',
        'methodController'    => 'DataAccessDashboardController@exportDashboardTimelineCsv',
        'namespaceController' => 'App\Http\Controllers\Api\V3',
        'middleware'          => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    // messages & required actions
    [
        'name'                => 'dar.dashboard.export.csv',
        'method'              => 'get',
        'path'                => '/teams/{id}/dar/dashboard/required-actions/export/csv',
        'methodController'    => 'DataAccessDashboardController@exportRequiredActionsCsv',
        'namespaceController' => 'App\Http\Controllers\Api\V3',
        'middleware'          => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

];
