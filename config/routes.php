<?php

return [
    // public
    'public' => [],

    // private
    'private' => [
        [
            'name' => 'team.federation',
            'method' => 'get',
            'path' => '/teams/{teamId}/federations',
            'methodController' => 'FederationController@index',
            'middleware' => [
                'check.access:permissions,integrations.metadata',
            ],
            'constraint' => [
                'teamId' => '[0-9]+',
            ],
        ],
        [
            'name' => 'team.federation',
            'method' => 'get',
            'path' => '/teams/{teamId}/federations/{federationId}',
            'methodController' => 'FederationController@show',
            'middleware' => [
                'check.access:permissions,integrations.metadata',
            ],
            'constraint' => [
                'teamId' => '[0-9]+',
                'federationId' => '[0-9]+',
            ],
        ],
        [
            'name' => 'team.federation',
            'method' => 'post',
            'path' => '/teams/{teamId}/federations',
            'methodController' => 'FederationController@store',
            'middleware' => [
                'check.access:permissions,integrations.metadata',
            ],
            'constraint' => [
                'teamId' => '[0-9]+',
            ],
        ],
        [
            'name' => 'team.federation',
            'method' => 'put',
            'path' => '/teams/{teamId}/federations/{federationId}',
            'methodController' => 'FederationController@update',
            'middleware' => [
                'check.access:permissions,integrations.metadata',
            ],
            'constraint' => [
                'teamId' => '[0-9]+',
                'federationId' => '[0-9]+',
            ],
        ],
        [
            'name' => 'team.federation',
            'method' => 'patch',
            'path' => '/teams/{teamId}/federations/{federationId}',
            'methodController' => 'FederationController@edit',
            'middleware' => [
                'check.access:permissions,integrations.metadata',
            ],
            'constraint' => [
                'teamId' => '[0-9]+',
                'federationId' => '[0-9]+',
            ],
        ],
        [
            'name' => 'team.federation',
            'method' => 'delete',
            'path' => '/teams/{teamId}/federations/{federationId}',
            'methodController' => 'FederationController@destroy',
            'middleware' => [
                'check.access:permissions,integrations.metadata',
            ],
            'constraint' => [
                'teamId' => '[0-9]+',
                'federationId' => '[0-9]+',
            ],
        ],
    ],

];