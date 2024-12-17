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
        'constraint' => [],
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
        'constraint' => [],
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
        'constraint' => [],
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
        'constraint' => [],
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
        'constraint' => [],
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
        'constraint' => [],
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
        'constraint' => [],
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
        'constraint' => [],
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
        'constraint' => [],
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
        'constraint' => [],
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
        ],
    ],
];
