<?php

return [
    // register
    [
        'name' => 'register',
        'method' => 'post',
        'path' => '/register',
        'methodController' => 'RegisterController@create',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'register',
        'method' => 'post',
        'path' => '/auth',
        'methodController' => 'AuthController@checkAuthorization',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],

    // login for:  google || linkedin || azure
    [
        'name' => 'login.social',
        'method' => 'get',
        'path' => '/auth/{provider}',
        'methodController' => 'SocialLoginController@login',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [
            'provider' => 'google|linkedin|azure',
        ],
    ],
    [
        'name' => 'login.social',
        'method' => 'get',
        'path' => '/auth/{provider}/callback',
        'methodController' => 'SocialLoginController@callback',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [
            'provider' => 'google|linkedin|azure',
        ],
    ],

    // tags
    [
        'name' => 'tags',
        'method' => 'get',
        'path' => '/tags',
        'methodController' => 'TagController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'tags',
        'method' => 'get',
        'path' => '/tags/{id}',
        'methodController' => 'TagController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'tags',
        'method' => 'post',
        'path' => '/tags',
        'methodController' => 'TagController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'tags',
        'method' => 'put',
        'path' => '/tags/{id}',
        'methodController' => 'TagController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'tags',
        'method' => 'patch',
        'path' => '/tags/{id}',
        'methodController' => 'TagController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'tags',
        'method' => 'delete',
        'path' => '/tags/{id}',
        'methodController' => 'TagController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // features
    [
        'name' => 'features',
        'method' => 'get',
        'path' => '/features',
        'methodController' => 'FeatureController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'features',
        'method' => 'get',
        'path' => '/features/{id}',
        'methodController' => 'FeatureController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'features',
        'method' => 'post',
        'path' => '/features',
        'methodController' => 'FeatureController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,features.create',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'features',
        'method' => 'put',
        'path' => '/features/{id}',
        'methodController' => 'FeatureController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,features.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'features',
        'method' => 'patch',
        'path' => '/features/{id}',
        'methodController' => 'FeatureController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,features.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'features',
        'method' => 'delete',
        'path' => '/features/{id}',
        'methodController' => 'FeatureController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,features.delete',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // filters
    [
        'name' => 'filters',
        'method' => 'get',
        'path' => '/filters',
        'methodController' => 'FilterController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'filters',
        'method' => 'get',
        'path' => '/filters/{id}',
        'methodController' => 'FilterController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'filters',
        'method' => 'post',
        'path' => '/filters',
        'methodController' => 'FilterController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,filters.create',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'filters',
        'method' => 'put',
        'path' => '/filters/{id}',
        'methodController' => 'FilterController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,filters.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'filters',
        'method' => 'patch',
        'path' => '/filters/{id}',
        'methodController' => 'FilterController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,filters.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'filters',
        'method' => 'delete',
        'path' => '/filters/{id}',
        'methodController' => 'FilterController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,filters.delete',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // dar-integrations
    [
        'name' => 'dar.integrations',
        'method' => 'get',
        'path' => '/dar-integrations',
        'methodController' => 'DarIntegrationController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,integrations.dar',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'dar.integrations',
        'method' => 'get',
        'path' => '/dar-integrations/{id}',
        'methodController' => 'DarIntegrationController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,integrations.dar',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar.integrations',
        'method' => 'post',
        'path' => '/dar-integrations',
        'methodController' => 'DarIntegrationController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,integrations.dar',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'dar.integrations',
        'method' => 'put',
        'path' => '/dar-integrations/{id}',
        'methodController' => 'DarIntegrationController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,integrations.dar',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar.integrations',
        'method' => 'patch',
        'path' => '/dar-integrations/{id}',
        'methodController' => 'DarIntegrationController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,integrations.dar',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar.integrations',
        'method' => 'delete',
        'path' => '/dar-integrations/{id}',
        'methodController' => 'DarIntegrationController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,integrations.dar',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // teams
    [
        'name' => 'teams',
        'method' => 'get',
        'path' => '/teams',
        'methodController' => 'TeamController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'teams',
        'method' => 'get',
        'path' => '/teams/{id}',
        'methodController' => 'TeamController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'teams',
        'method' => 'post',
        'path' => '/teams',
        'methodController' => 'TeamController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'teams',
        'method' => 'put',
        'path' => '/teams/{id}',
        'methodController' => 'TeamController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'teams',
        'method' => 'patch',
        'path' => '/teams/{id}',
        'methodController' => 'TeamController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'teams',
        'method' => 'delete',
        'path' => '/teams/{id}',
        'methodController' => 'TeamController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // tools
    [
        'name' => 'tools',
        'method' => 'get',
        'path' => '/tools',
        'methodController' => 'ToolController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'tools',
        'method' => 'get',
        'path' => '/tools/{id}',
        'methodController' => 'ToolController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,tools.create',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'tools',
        'method' => 'put',
        'path' => '/tools/{id}',
        'methodController' => 'ToolController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,tools.update',
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,tools.update',
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,tools.delete',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // activity.logs
    [
        'name' => 'activity.logs',
        'method' => 'get',
        'path' => '/activity_logs',
        'methodController' => 'ActivityLogController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,audit.read',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'activity.logs',
        'method' => 'get',
        'path' => '/activity_logs/{id}',
        'methodController' => 'ActivityLogController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,audit.read',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'activity.logs',
        'method' => 'post',
        'path' => '/activity_logs',
        'methodController' => 'ActivityLogController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,audit.create',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'activity.logs',
        'method' => 'put',
        'path' => '/activity_logs/{id}',
        'methodController' => 'ActivityLogController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,audit.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'activity.logs',
        'method' => 'patch',
        'path' => '/activity_logs/{id}',
        'methodController' => 'ActivityLogController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,audit.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'activity.logs',
        'method' => 'delete',
        'path' => '/activity_logs/{id}',
        'methodController' => 'ActivityLogController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,audit.delete',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // activity.logs.types
    [
        'name' => 'activity.logs.types',
        'method' => 'get',
        'path' => '/activity_log_types',
        'methodController' => 'ActivityLogTypeController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'activity.logs.types',
        'method' => 'get',
        'path' => '/activity_log_types/{id}',
        'methodController' => 'ActivityLogTypeController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'activity.logs.types',
        'method' => 'post',
        'path' => '/activity_log_types',
        'methodController' => 'ActivityLogTypeController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'activity.logs.types',
        'method' => 'put',
        'path' => '/activity_log_types/{id}',
        'methodController' => 'ActivityLogTypeController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'activity.logs.types',
        'method' => 'patch',
        'path' => '/activity_log_types/{id}',
        'methodController' => 'ActivityLogTypeController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'activity.logs.types',
        'method' => 'delete',
        'path' => '/activity_log_types/{id}',
        'methodController' => 'ActivityLogTypeController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // activity.logs.user.types
    [
        'name' => 'activity.logs.user.types',
        'method' => 'get',
        'path' => '/activity_log_user_types',
        'methodController' => 'ActivityLogUserTypeController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'activity.logs.user.types',
        'method' => 'get',
        'path' => '/activity_log_user_types/{id}',
        'methodController' => 'ActivityLogUserTypeController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'activity.logs.user.types',
        'method' => 'post',
        'path' => '/activity_log_user_types',
        'methodController' => 'ActivityLogUserTypeController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'activity.logs.user.types',
        'method' => 'put',
        'path' => '/activity_log_user_types/{id}',
        'methodController' => 'ActivityLogUserTypeController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'activity.logs.user.types',
        'method' => 'patch',
        'path' => '/activity_log_user_types/{id}',
        'methodController' => 'ActivityLogUserTypeController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'activity.logs.user.types',
        'method' => 'delete',
        'path' => '/activity_log_user_types/{id}',
        'methodController' => 'ActivityLogUserTypeController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdr.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // permissions
    [
        'name' => 'permissions',
        'method' => 'get',
        'path' => '/permissions',
        'methodController' => 'PermissionController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'permissions',
        'method' => 'get',
        'path' => '/permissions/{id}',
        'methodController' => 'PermissionController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'permissions',
        'method' => 'post',
        'path' => '/permissions',
        'methodController' => 'PermissionController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'permissions',
        'method' => 'put',
        'path' => '/permissions/{id}',
        'methodController' => 'PermissionController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'permissions',
        'method' => 'patch',
        'path' => '/permissions/{id}',
        'methodController' => 'PermissionController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'permissions',
        'method' => 'delete',
        'path' => '/permissions/{id}',
        'methodController' => 'PermissionController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // users
    [
        'name' => 'users',
        'method' => 'get',
        'path' => '/users',
        'methodController' => 'UserController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'users',
        'method' => 'get',
        'path' => '/users/{id}',
        'methodController' => 'UserController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'users',
        'method' => 'post',
        'path' => '/users',
        'methodController' => 'UserController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'users',
        'method' => 'put',
        'path' => '/users/{id}',
        'methodController' => 'UserController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'users',
        'method' => 'patch',
        'path' => '/users/{id}',
        'methodController' => 'UserController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'users',
        'method' => 'delete',
        'path' => '/users/{id}',
        'methodController' => 'UserController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // notifications
    [
        'name' => 'notifications',
        'method' => 'get',
        'path' => '/notifications',
        'methodController' => 'NotificationController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'notifications',
        'method' => 'get',
        'path' => '/notifications/{id}',
        'methodController' => 'NotificationController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'notifications',
        'method' => 'post',
        'path' => '/notifications',
        'methodController' => 'NotificationController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'notifications',
        'method' => 'put',
        'path' => '/notifications/{id}',
        'methodController' => 'NotificationController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'notifications',
        'method' => 'patch',
        'path' => '/notifications/{id}',
        'methodController' => 'NotificationController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'notifications',
        'method' => 'delete',
        'path' => '/notifications/{id}',
        'methodController' => 'NotificationController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // reviews
    [
        'name' => 'reviews',
        'method' => 'get',
        'path' => '/reviews',
        'methodController' => 'ReviewController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'reviews',
        'method' => 'get',
        'path' => '/reviews/{id}',
        'methodController' => 'ReviewController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'reviews',
        'method' => 'post',
        'path' => '/reviews',
        'methodController' => 'ReviewController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'reviews',
        'method' => 'put',
        'path' => '/reviews/{id}',
        'methodController' => 'ReviewController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'reviews',
        'method' => 'patch',
        'path' => '/reviews/{id}',
        'methodController' => 'ReviewController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'reviews',
        'method' => 'delete',
        'path' => '/reviews/{id}',
        'methodController' => 'ReviewController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // sectors
    [
        'name' => 'sectors',
        'method' => 'get',
        'path' => '/sectors',
        'methodController' => 'SectorController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'sectors',
        'method' => 'get',
        'path' => '/sectors/{id}',
        'methodController' => 'SectorController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'sectors',
        'method' => 'post',
        'path' => '/sectors',
        'methodController' => 'SectorController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'sectors',
        'method' => 'put',
        'path' => '/sectors/{id}',
        'methodController' => 'SectorController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'sectors',
        'method' => 'patch',
        'path' => '/sectors/{id}',
        'methodController' => 'SectorController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'sectors',
        'method' => 'delete',
        'path' => '/sectors/{id}',
        'methodController' => 'SectorController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // collections
    [
        'name' => 'collections',
        'method' => 'get',
        'path' => '/collections',
        'methodController' => 'CollectionController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'collections',
        'method' => 'get',
        'path' => '/collections/{id}',
        'methodController' => 'CollectionController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
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
        'method' => 'post',
        'path' => '/collections',
        'methodController' => 'CollectionController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // audit.logs
    [
        'name' => 'audit.logs',
        'method' => 'get',
        'path' => '/audit_logs',
        'methodController' => 'AuditLogController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'audit.logs',
        'method' => 'get',
        'path' => '/audit_logs/{id}',
        'methodController' => 'AuditLogController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'audit.logs',
        'method' => 'post',
        'path' => '/audit_logs',
        'methodController' => 'AuditLogController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'audit.logs',
        'method' => 'put',
        'path' => '/audit_logs/{id}',
        'methodController' => 'AuditLogController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'audit.logs',
        'method' => 'patch',
        'path' => '/audit_logs/{id}',
        'methodController' => 'AuditLogController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'audit.logs',
        'method' => 'delete',
        'path' => '/audit_logs/{id}',
        'methodController' => 'AuditLogController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // data.use.registers
    [
        'name' => 'data.use.registers',
        'method' => 'get',
        'path' => '/data_use_registers',
        'methodController' => 'DataUseRegisterController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'data.use.registers',
        'method' => 'get',
        'path' => '/data_use_registers/{id}',
        'methodController' => 'DataUseRegisterController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'data.use.registers',
        'method' => 'post',
        'path' => '/data_use_registers',
        'methodController' => 'DataUseRegisterController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'data.use.registers',
        'method' => 'put',
        'path' => '/data_use_registers/{id}',
        'methodController' => 'DataUseRegisterController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'data.use.registers',
        'method' => 'patch',
        'path' => '/data_use_registers/{id}',
        'methodController' => 'DataUseRegisterController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'data.use.registers',
        'method' => 'delete',
        'path' => '/data_use_registers/{id}',
        'methodController' => 'DataUseRegisterController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // applications
    [
        'name' => 'applications',
        'method' => 'get',
        'path' => '/applications',
        'methodController' => 'ApplicationController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'applications',
        'method' => 'get',
        'path' => '/applications/{id}',
        'methodController' => 'ApplicationController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'applications',
        'method' => 'post',
        'path' => '/applications',
        'methodController' => 'ApplicationController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'applications',
        'method' => 'put',
        'path' => '/applications/{id}',
        'methodController' => 'ApplicationController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'applications',
        'method' => 'patch',
        'path' => '/applications/{id}',
        'methodController' => 'ApplicationController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'applications',
        'method' => 'delete',
        'path' => '/applications/{id}',
        'methodController' => 'ApplicationController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // roles
    [
        'name' => 'roles',
        'method' => 'get',
        'path' => '/roles',
        'methodController' => 'RoleController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'roles',
        'method' => 'get',
        'path' => '/roles/{id}',
        'methodController' => 'RoleController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'roles',
        'method' => 'post',
        'path' => '/roles',
        'methodController' => 'RoleController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'roles',
        'method' => 'put',
        'path' => '/roles/{id}',
        'methodController' => 'RoleController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'roles',
        'method' => 'patch',
        'path' => '/roles/{id}',
        'methodController' => 'RoleController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'roles',
        'method' => 'delete',
        'path' => '/roles/{id}',
        'methodController' => 'RoleController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // emailtemplates
    [
        'name' => 'emailtemplates',
        'method' => 'get',
        'path' => '/emailtemplates',
        'methodController' => 'EmailTemplateController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'emailtemplates',
        'method' => 'get',
        'path' => '/emailtemplates/{id}',
        'methodController' => 'EmailTemplateController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'emailtemplates',
        'method' => 'post',
        'path' => '/emailtemplates',
        'methodController' => 'EmailTemplateController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'emailtemplates',
        'method' => 'put',
        'path' => '/emailtemplates/{id}',
        'methodController' => 'EmailTemplateController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'emailtemplates',
        'method' => 'patch',
        'path' => '/emailtemplates/{id}',
        'methodController' => 'EmailTemplateController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'emailtemplates',
        'method' => 'delete',
        'path' => '/emailtemplates/{id}',
        'methodController' => 'EmailTemplateController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],



    // team.user.role
    [
        'name' => 'team.user.role',
        'method' => 'post',
        'path' => '/teams/{teamId}/users',
        'methodController' => 'TeamUserController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'team.user.role',
        'method' => 'put',
        'path' => '/teams/{teamId}/users/{userId}',
        'methodController' => 'TeamUserController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],

    // logout
    [
        'name' => 'logout',
        'method' => 'post',
        'path' => '/logout',
        'methodController' => 'LogoutController@logout',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],

    // datasets
    [
        'name' => 'datasets',
        'method' => 'get',
        'path' => '/datasets',
        'methodController' => 'DatasetController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'datasets',
        'method' => 'get',
        'path' => '/datasets/{id}',
        'methodController' => 'DatasetController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'datasets',
        'method' => 'post',
        'path' => '/datasets',
        'methodController' => 'DatasetController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'datasets',
        'method' => 'delete',
        'path' => '/datasets/{id}',
        'methodController' => 'DatasetController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,integrations.metadata',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
            'federationId' => '[0-9]+',
        ],
    ],

];