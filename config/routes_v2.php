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
            'check.access:permissions,collections.create',
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
            'check.access:permissions,collections.update',
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
            'check.access:permissions,collections.update',
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
            'check.access:permissions,collections.delete',
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
            'sanitize.input',
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
            'sanitize.input',
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
            'sanitize.input',
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
            'id' => '[0-9]+',
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
            'id' => '[0-9]+',
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

    // publications
    [
        'name' => 'publications.index',
        'method' => 'get',
        'path' => '/publications',
        'methodController' => 'PublicationController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'publications-count-field',
        'method' => 'get',
        'path' => '/publications/count/{field}',
        'methodController' => 'PublicationController@count',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'publications.show',
        'method' => 'get',
        'path' => '/publications/{id}',
        'methodController' => 'PublicationController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'publications.edit',
        'method' => 'patch',
        'path' => '/publications/{id}',
        'methodController' => 'PublicationController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'publications.update',
        'method' => 'put',
        'path' => '/publications/{id}',
        'methodController' => 'PublicationController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'publications.create',
        'method' => 'post',
        'path' => '/publications',
        'methodController' => 'PublicationController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [],
    ],
    [
        'name' => 'publications.destroy',
        'method' => 'delete',
        'path' => '/publications/{id}',
        'methodController' => 'PublicationController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // teams & publications
    [
        'name' => 'publications.get.active',
        'method' => 'get',
        'path' => '/teams/{teamId}/publications',
        'methodController' => 'TeamPublicationController@indexStatus',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'publications.get.active',
        'method' => 'get',
        'path' => '/teams/{teamId}/publications/status/{status}',
        'methodController' => 'TeamPublicationController@indexStatus',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'teamId' => '[0-9]+',
            'status' => 'active|draft|archived'
        ],
    ],
    [
        'name' => 'publications.get.one',
        'method' => 'get',
        'path' => '/teams/{teamId}/publications/{id}',
        'methodController' => 'TeamPublicationController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'teamId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'publications.get.one',
        'method' => 'get',
        'path' => '/teams/{teamId}/publications/{id}/status/{status}',
        'methodController' => 'TeamPublicationController@showStatus',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'teamId' => '[0-9]+',
            'id' => '[0-9]+',
            'status' => 'active|draft|archived'
        ],
    ],
    [
        'name' => 'publications.create',
        'method' => 'post',
        'path' => '/teams/{teamId}/publications',
        'methodController' => 'TeamPublicationController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'publications.update',
        'method' => 'put',
        'path' => '/teams/{teamId}/publications/{id}',
        'methodController' => 'TeamPublicationController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'teamId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'publications.edit',
        'method' => 'patch',
        'path' => '/teams/{teamId}/publications/{id}',
        'methodController' => 'TeamPublicationController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'teamId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'publications.destroy',
        'method' => 'delete',
        'path' => '/teams/{teamId}/publications/{id}',
        'methodController' => 'TeamPublicationController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'teamId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],

    // user & publications
    [
        'name' => 'publications.get.active',
        'method' => 'get',
        'path' => '/users/{userId}/publications',
        'methodController' => 'UserPublicationController@indexStatus',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'publications.get.active',
        'method' => 'get',
        'path' => '/users/{userId}/publications/status/{status}',
        'methodController' => 'UserPublicationController@indexStatus',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'userId' => '[0-9]+',
            'status' => 'active|draft|archived'
        ],
    ],
    [
        'name' => 'publications.get.one',
        'method' => 'get',
        'path' => '/users/{userId}/publications/{id}',
        'methodController' => 'UserPublicationController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'userId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'publications.get.one',
        'method' => 'get',
        'path' => '/users/{userId}/publications/{id}/status/{status}',
        'methodController' => 'UserPublicationController@showStatus',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'userId' => '[0-9]+',
            'id' => '[0-9]+',
            'status' => 'active|draft|archived'
        ],
    ],
    [
        'name' => 'publications.create',
        'method' => 'post',
        'path' => '/users/{userId}/publications',
        'methodController' => 'UserPublicationController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'publications.update',
        'method' => 'put',
        'path' => '/users/{userId}/publications/{id}',
        'methodController' => 'UserPublicationController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'userId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'publications.edit',
        'method' => 'patch',
        'path' => '/users/{userId}/publications/{id}',
        'methodController' => 'UserPublicationController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'userId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'publications.destroy',
        'method' => 'delete',
        'path' => '/users/{userId}/publications/{id}',
        'methodController' => 'UserPublicationController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'userId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],

    // tools
    [
        'name' => 'tools',
        'method' => 'get',
        'path' => '/tools',
        'methodController' => 'ToolController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'tools-count-field',
        'method' => 'get',
        'path' => '/tools/count/{field}',
        'methodController' => 'ToolController@count',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'tools',
        'method' => 'get',
        'path' => '/tools/{id}',
        'methodController' => 'ToolController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'tools',
        'method' => 'post',
        'path' => '/tools',
        'methodController' => 'ToolController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'tools',
        'method' => 'put',
        'path' => '/tools/{id}',
        'methodController' => 'ToolController@update',
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
        'name' => 'tools',
        'method' => 'patch',
        'path' => '/tools/{id}',
        'methodController' => 'ToolController@edit',
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
        'name' => 'tools',
        'method' => 'delete',
        'path' => '/tools/{id}',
        'methodController' => 'ToolController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // teams & tools
    [
        'name' => 'tools.get.active',
        'method' => 'get',
        'path' => '/teams/{teamId}/tools',
        'methodController' => 'TeamToolController@indexStatus',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'tools.get.active',
        'method' => 'get',
        'path' => '/teams/{teamId}/tools/status/{status}',
        'methodController' => 'TeamToolController@indexStatus',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'teamId' => '[0-9]+',
            'status' => 'active|draft|archived'
        ],
    ],
    [
        'name' => 'tools.get.one',
        'method' => 'get',
        'path' => '/teams/{teamId}/tools/{id}',
        'methodController' => 'TeamToolController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'teamId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'tools.get.one',
        'method' => 'get',
        'path' => '/teams/{teamId}/tools/{id}/status/{status}',
        'methodController' => 'TeamToolController@showStatus',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'teamId' => '[0-9]+',
            'id' => '[0-9]+',
            'status' => 'active|draft|archived'
        ],
    ],
    [
        'name' => 'tools.create',
        'method' => 'post',
        'path' => '/teams/{teamId}/tools',
        'methodController' => 'TeamToolController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'tools.update',
        'method' => 'put',
        'path' => '/teams/{teamId}/tools/{id}',
        'methodController' => 'TeamToolController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'teamId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'publications.edit',
        'method' => 'patch',
        'path' => '/teams/{teamId}/tools/{id}',
        'methodController' => 'TeamToolController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'teamId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'publications.destroy',
        'method' => 'delete',
        'path' => '/teams/{teamId}/tools/{id}',
        'methodController' => 'TeamToolController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'teamId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],


    // users & tools
    [
        'name' => 'tools.get.active',
        'method' => 'get',
        'path' => '/users/{userId}/tools',
        'methodController' => 'UserToolController@indexStatus',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'tools.get.active',
        'method' => 'get',
        'path' => '/users/{userId}/tools/status/{status}',
        'methodController' => 'UserToolController@indexStatus',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'userId' => '[0-9]+',
            'status' => 'active|draft|archived'
        ],
    ],
    [
        'name' => 'tools.get.one',
        'method' => 'get',
        'path' => '/users/{userId}/tools/{id}',
        'methodController' => 'UserToolController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'userId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'tools.get.one',
        'method' => 'get',
        'path' => '/users/{userId}/tools/{id}/status/{status}',
        'methodController' => 'UserToolController@showStatus',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'userId' => '[0-9]+',
            'id' => '[0-9]+',
            'status' => 'active|draft|archived'
        ],
    ],
    [
        'name' => 'tools.create',
        'method' => 'post',
        'path' => '/users/{userId}/tools',
        'methodController' => 'UserToolController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'tools.update',
        'method' => 'put',
        'path' => '/users/{userId}/tools/{id}',
        'methodController' => 'UserToolController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'userId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'publications.edit',
        'method' => 'patch',
        'path' => '/users/{userId}/tools/{id}',
        'methodController' => 'UserToolController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'userId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'publications.destroy',
        'method' => 'delete',
        'path' => '/users/{userId}/tools/{id}',
        'methodController' => 'UserToolController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => ['jwt.verify'],
        'constraint' => [
            'userId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],
];
