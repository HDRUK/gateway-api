<?php

return [
    // public
    'public' => [],

    // private
    'private' => [
        // team.user.role
        [
            'name' => 'team.user.role',
            'method' => 'post',
            'path' => '/teams/{teamId}/users',
            'methodController' => 'TeamUserController@store',
            'middleware' => [],
            'constraint' => [
                'teamId', '[0-9]+'
            ],
        ],
        [
            'name' => 'team.user.role',
            'method' => 'put',
            'path' => '/teams/{teamId}/users/{userId}',
            'methodController' => 'TeamUserController@update',
            'middleware' => [],
            'constraint' => [
                'teamId' => '[0-9]+', 
                'userId' => '[0-9]+',
            ],
        ],
        [
            'name' => 'team.user.role',
            'method' => 'delete',
            'path' => '/teams/{teamId}/users/{userId}',
            'methodController' => 'TeamUserController@destroy',
            'middleware' => [],
            'constraint' => [
                'teamId' => '[0-9]+',
                'userId' => '[0-9]+',
            ],
        ],

        // dispatch.email
        [
            'name' => 'dispatch.email',
            'method' => 'post',
            'path' => '/dispatch_email',
            'methodController' => 'EmailController@dispatchEmail',
            'middleware' => [],
            'constraint' => [],
        ],

        // logout
        [
            'name' => 'logout',
            'method' => 'post',
            'path' => '/logout',
            'methodController' => 'LogoutController@logout',
            'middleware' => [],
            'constraint' => [],
        ],

        // datasets
        [
            'name' => 'datasets',
            'method' => 'get',
            'path' => '/datasets',
            'methodController' => 'DatasetController@index',
            'middleware' => [],
            'constraint' => [],
        ],
        [
            'name' => 'datasets',
            'method' => 'get',
            'path' => '/datasets/{id}',
            'methodController' => 'DatasetController@show',
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
            'middleware' => [],
            'constraint' => [],
        ],
        [
            'name' => 'datasets',
            'method' => 'post',
            'path' => '/datasets/{id}',
            'methodController' => 'DatasetController@destroy',
            'middleware' => [],
            'constraint' => [
                'id', '[0-9]+'
            ],
        ],

        // team.notification
        [
            'name' => 'team.notification',
            'method' => 'post',
            'path' => '/teams/{teamId}/notifications',
            'methodController' => 'TeamNotificationController@storeTeamNotification',
            'middleware' => [
                'check.access:roles,custodian.team.admin',
            ],
            'constraint' => [
                'teamId' => '[0-9]+',
            ],
        ],
        [
            'name' => 'team.notification',
            'method' => 'put',
            'path' => '/teams/{teamId}/notifications/{notificationId}',
            'methodController' => 'TeamNotificationController@updateTeamNotification',
            'middleware' => [
                'check.access:roles,custodian.team.admin',
            ],
            'constraint' => [
                'teamId' => '[0-9]+',
                'notificationId' => '[0-9]+',
            ],
        ],
        [
            'name' => 'team.notification',
            'method' => 'delete',
            'path' => '/teams/{teamId}/notifications/{notificationId}',
            'methodController' => 'TeamNotificationController@destroyTeamNotification',
            'middleware' => [
                'check.access:roles,custodian.team.admin',
            ],
            'constraint' => [
                'teamId' => '[0-9]+',
                'notificationId' => '[0-9]+',
            ],
        ],

        // team.federation
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