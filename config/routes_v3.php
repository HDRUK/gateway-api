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
            'id' => '[0-9]+',
        ],
    ],
];
