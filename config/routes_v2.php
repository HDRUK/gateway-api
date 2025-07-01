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
        'path' => '/datasets/{id}',
        'methodController' => 'DatasetController@showActive',
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
            'sanitize.input',
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
            'sanitize.input',
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
        'path' => '/teams/{teamId}/datasets/status/{status}',
        'methodController' => 'TeamDatasetController@indexStatus',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
            'status' => 'active|draft|archived'
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
            'check.access:permissions,datasets.create',
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
            'check.access:permissions,datasets.update',
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
            'check.access:permissions,datasets.update',
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
            'check.access:permissions,datasets.delete',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
        ],
    ],

    // v2 data uses
    [
        'name' => 'durs.get.active',
        'method' => 'get',
        'path' => '/dur',
        'methodController' => 'DurController@indexActive',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'durs.export',
        'method' => 'get',
        'path' => '/dur/export',
        'methodController' => 'DurController@export',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'durs.exportTemplate',
        'method' => 'get',
        'path' => '/dur/template',
        'methodController' => 'DurController@exportTemplate',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'durs.get.one',
        'method' => 'get',
        'path' => '/dur/{id}',
        'methodController' => 'DurController@showActive',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [],
        'constraint' => [],
    ],

    // v2 team & data uses
    [
        'name' => 'team.durs.indexStatus',
        'method' => 'get',
        'path' => '/teams/{teamId}/dur/status/{status}',
        'methodController' => 'TeamDurController@indexStatus',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
            'status' => 'active|draft|archived'
        ],
    ],
    [
        'name' => 'team.durs.count',
        'method' => 'get',
        'path' => '/teams/{teamId}/dur/count/{field}',
        'methodController' => 'TeamDurController@count',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
            'field' => 'status'
        ],
    ],
    [
        'name' => 'team.durs.get.one',
        'method' => 'get',
        'path' => '/teams/{teamId}/dur/{id}',
        'methodController' => 'TeamDurController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'team.durs.create',
        'method' => 'post',
        'path' => '/teams/{teamId}/dur',
        'methodController' => 'TeamDurController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,dur.create',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'team.durs.update',
        'method' => 'put',
        'path' => '/teams/{teamId}/dur/{id}',
        'methodController' => 'TeamDurController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,dur.update',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'team.durs.edit',
        'method' => 'patch',
        'path' => '/teams/{teamId}/dur/{id}',
        'methodController' => 'TeamDurController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,dur.update',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'team.durs.delete',
        'method' => 'delete',
        'path' => '/teams/{teamId}/dur/{id}',
        'methodController' => 'TeamDurController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,dur.delete',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
            'id' => '[0-9]+',
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
        'methodController' => 'ToolController@showActive',
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
            'check.access:permissions,tools.create',
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
            'check.access:permissions,tools.update',
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
            'check.access:permissions,tools.update',
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
            'check.access:permissions,tools.delete',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // teams & tools
    [
        'name' => 'team.tools.get.active',
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
        'name' => 'team.tools.get.active',
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
        'name' => 'team.tools.count',
        'method' => 'get',
        'path' => '/teams/{teamId}/tools/count/{field}',
        'methodController' => 'TeamToolController@count',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'team.tools.get.one',
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
        'name' => 'team.tools.create',
        'method' => 'post',
        'path' => '/teams/{teamId}/tools',
        'methodController' => 'TeamToolController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,tools.create',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'team.tools.update',
        'method' => 'put',
        'path' => '/teams/{teamId}/tools/{id}',
        'methodController' => 'TeamToolController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,tools.update',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'team.tools.edit',
        'method' => 'patch',
        'path' => '/teams/{teamId}/tools/{id}',
        'methodController' => 'TeamToolController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,tools.update',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'team.tools.destroy',
        'method' => 'delete',
        'path' => '/teams/{teamId}/tools/{id}',
        'methodController' => 'TeamToolController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,tools.delete',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
            'id' => '[0-9]+',
        ],
    ],


    // users & tools
    [
        'name' => 'user.tools.get.active',
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
        'name' => 'user.tools.get.active',
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
        'name' => 'user.tools.get.count',
        'method' => 'get',
        'path' => '/users/{userId}/tools/count/{field}',
        'methodController' => 'UserToolController@count',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'user.tools.get.one',
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
        'name' => 'user.tools.create',
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
        'name' => 'user.tools.update',
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
        'name' => 'user.tools.edit',
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
        'name' => 'user.tools.destroy',
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

     // Data Custodian Networks
    [
        'name' => 'data_custodian_networks.get',
        'method' => 'get',
        'path' => '/data_custodian_networks',
        'methodController' => 'DataCustodianNetworksController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'data_custodian_networks.get.one',
        'method' => 'get',
        'path' => '/data_custodian_networks/{id}',
        'methodController' => 'DataCustodianNetworksController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'data_custodian_networks.get.one.summary',
        'method' => 'get',
        'path' => '/data_custodian_networks/{id}/summary',
        'methodController' => 'DataCustodianNetworksController@showSummary',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'data_custodian_networks.create',
        'method' => 'post',
        'path' => '/data_custodian_networks',
        'methodController' => 'DataCustodianNetworksController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'data_custodian_networks.update',
        'method' => 'put',
        'path' => '/data_custodian_networks/{id}',
        'methodController' => 'DataCustodianNetworksController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'data_custodian_networks.edit',
        'method' => 'patch',
        'path' => '/data_custodian_networks/{id}',
        'methodController' => 'DataCustodianNetworksController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'data_custodian_networks.destroy',
        'method' => 'delete',
        'path' => '/data_custodian_networks/{id}',
        'methodController' => 'DataCustodianNetworksController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V2',
        'middleware' => [
            'jwt.verify',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
];
