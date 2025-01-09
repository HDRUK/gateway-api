<?php

return [
    // v2 collections
    [
        'name' => 'collections',
        'method' => 'get',
        'path' => '/collections',
        'methodController' => 'CollectionController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'collections',
        'method' => 'get',
        'path' => '/collections/count/{field}',
        'methodController' => 'CollectionController@count',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'sanitize.input',
        ],
        'constraint' => [
            'field' => '[A-Za-z]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'get',
        'path' => '/collections/{id}',
        'methodController' => 'CollectionController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'post',
        'path' => '/collections',
        'methodController' => 'CollectionController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'collections',
        'method' => 'put',
        'path' => '/collections/{id}',
        'methodController' => 'CollectionController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'patch',
        'path' => '/collections/{id}',
        'methodController' => 'CollectionController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'delete',
        'path' => '/collections/{id}',
        'methodController' => 'CollectionController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // v2 users/collections
    [
        'name' => 'collections',
        'method' => 'get',
        'path' => '/users/{userId}/collections',
        'methodController' => 'UserCollectionController@indexActive',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'get',
        'path' => '/users/{userId}/collections/status/draft',
        'methodController' => 'UserCollectionController@indexDraft',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'get',
        'path' => '/users/{userId}/collections/status/archived',
        'methodController' => 'UserCollectionController@indexArchived',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'get',
        'path' => '/users/{userId}/collections/{id}',
        'methodController' => 'UserCollectionController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'userId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'post',
        'path' => '/users/{userId}/collections',
        'methodController' => 'UserCollectionController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'put',
        'path' => 'users/{userId}/collections/{id}',
        'methodController' => 'UserCollectionController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'patch',
        'path' => 'users/{userId}/collections/{id}',
        'methodController' => 'UserCollectionController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'delete',
        'path' => 'users/{userId}/collections/{id}',
        'methodController' => 'UserCollectionController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'userId' => '[0-9]+',
        ],
    ],

    // v2 teams/collections
    [
        'name' => 'collections',
        'method' => 'get',
        'path' => '/teams/{teamId}/collections',
        'methodController' => 'TeamCollectionController@indexActive',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'get',
        'path' => '/teams/{teamId}/collections/status/draft',
        'methodController' => 'TeamCollectionController@indexDraft',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'get',
        'path' => '/teams/{teamId}/collections/status/archived',
        'methodController' => 'TeamCollectionController@indexArchived',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'get',
        'path' => '/teams/{teamId}/collections/{id}',
        'methodController' => 'TeamCollectionController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'post',
        'path' => '/teams/{teamId}/collections',
        'methodController' => 'TeamCollectionController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'put',
        'path' => 'teams/{teamId}/collections/{id}',
        'methodController' => 'TeamCollectionController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'patch',
        'path' => 'teams/{teamId}/collections/{id}',
        'methodController' => 'TeamCollectionController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'delete',
        'path' => 'teams/{teamId}/collections/{id}',
        'methodController' => 'TeamCollectionController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
        ],
    ],

    // v2 datasets
    [
        'name' => 'datasets',
        'method' => 'get',
        'path' => '/datasets',
        'methodController' => 'DatasetController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'datasets',
        'method' => 'get',
        'path' => '/datasets/count/{field}',
        'methodController' => 'DatasetController@count',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'datasets',
        'method' => 'get',
        'path' => '/datasets/{id}',
        'methodController' => 'DatasetController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'datasets',
        'method' => 'post',
        'path' => '/datasets',
        'methodController' => 'DatasetController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            //'sanitize.input',
            'check.access:permissions,datasets.create',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'datasets',
        'method' => 'put',
        'path' => '/datasets/{id}',
        'methodController' => 'DatasetController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            //'sanitize.input',
            'check.access:permissions,datasets.update',
        ],
        'constraint' => [
            'id', '[0-9]+'
        ],
    ],
    [
        'name' => 'datasets',
        'method' => 'patch',
        'path' => '/datasets/{id}',
        'methodController' => 'DatasetController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,datasets.update',
        ],
        'constraint' => [
            'id', '[0-9]+'
        ],
    ],
    [
        'name' => 'datasets',
        'method' => 'delete',
        'path' => '/datasets/{id}',
        'methodController' => 'DatasetController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,datasets.delete',
        ],
        'constraint' => [
            'id', '[0-9]+'
        ],
    ],

    // v2 teams/datasets
    [
        'name' => 'datasets',
        'method' => 'get',
        'path' => '/teams/{teamId}/datasets',
        'methodController' => 'TeamDatasetController@indexActive',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'datasets',
        'method' => 'get',
        'path' => '/teams/{teamId}/datasets/status/draft',
        'methodController' => 'TeamDatasetController@indexDraft',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'datasets',
        'method' => 'get',
        'path' => '/teams/{teamId}/datasets/status/archived',
        'methodController' => 'TeamDatasetController@indexArchived',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'datasets',
        'method' => 'get',
        'path' => '/teams/{teamId}/datasets/{id}',
        'methodController' => 'TeamDatasetController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'datasets',
        'method' => 'get',
        'path' => '/teams/{teamId}/datasets/count/{field}',
        'methodController' => 'TeamDatasetController@count',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'datasets',
        'method' => 'post',
        'path' => '/teams/{teamId}/datasets',
        'methodController' => 'TeamDatasetController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'datasets',
        'method' => 'put',
        'path' => '/teams/{teamId}/datasets/{id}',
        'methodController' => 'TeamDatasetController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'datasets',
        'method' => 'patch',
        'path' => '/teams/{teamId}/datasets/{id}',
        'methodController' => 'TeamDatasetController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'datasets',
        'method' => 'delete',
        'path' => '/teams/{teamId}/datasets/{id}',
        'methodController' => 'TeamDatasetController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
        ],
    ],
];
