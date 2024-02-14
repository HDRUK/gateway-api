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

    // login for:  google || azure || linkedin
    [
        'name' => 'login.social',
        'method' => 'get',
        'path' => '/auth/{provider}',
        'methodController' => 'SocialLoginController@login',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [
            'provider' => 'google|azure|linkedin',
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
            'provider' => 'google|azure|linkedin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
        ],
        'constraint' => [],
    ],
    [
        'name' => 'teams',
        'method' => 'get',
        'path' => '/teams/{teamId}',
        'methodController' => 'TeamController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
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
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'teams',
        'method' => 'put',
        'path' => '/teams/{teamId}',
        'methodController' => 'TeamController@update',
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
        'name' => 'teams',
        'method' => 'patch',
        'path' => '/teams/{teamId}',
        'methodController' => 'TeamController@edit',
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
        'name' => 'teams',
        'method' => 'delete',
        'path' => '/teams/{teamId}',
        'methodController' => 'TeamController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
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
            'check.access:permissions,tools.delete',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // tools integrations
    [
        'name' => 'tools.integrations',
        'method' => 'get',
        'path' => '/integrations/tools',
        'methodController' => 'ToolController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'tools.integrations',
        'method' => 'get',
        'path' => '/integrations/tools/{id}',
        'methodController' => 'ToolController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'tools.integrations',
        'method' => 'post',
        'path' => '/integrations/tools',
        'methodController' => 'ToolController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'tools.integrations',
        'method' => 'put',
        'path' => '/integrations/tools/{id}',
        'methodController' => 'ToolController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'tools.integrations',
        'method' => 'patch',
        'path' => '/integrations/tools/{id}',
        'methodController' => 'ToolController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'tools.integrations',
        'method' => 'delete',
        'path' => '/integrations/tools/{id}',
        'methodController' => 'ToolController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'permissions',
        'method' => 'get',
        'path' => '/permissions/{id}',
        'methodController' => 'PermissionController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'collections',
        'method' => 'get',
        'path' => '/collections/{id}',
        'methodController' => 'CollectionController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
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
            // 'sanitize.input',
            'check.access:permissions,collections.create',
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
            'check.access:permissions,collections.update',
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
            'check.access:permissions,collections.update',
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
            'check.access:permissions,collections.delete',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // collections integrations
    [
        'name' => 'collections.integrations',
        'method' => 'get',
        'path' => '/integrations/collections',
        'methodController' => 'CollectionController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'collections.integrations',
        'method' => 'get',
        'path' => '/integrations/collections/{id}',
        'methodController' => 'CollectionController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections.integrations',
        'method' => 'post',
        'path' => '/integrations/collections',
        'methodController' => 'CollectionController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'collections.integrations',
        'method' => 'put',
        'path' => '/integrations/collections/{id}',
        'methodController' => 'CollectionController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections.integrations',
        'method' => 'patch',
        'path' => '/integrations/collections/{id}',
        'methodController' => 'CollectionController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections.integrations',
        'method' => 'delete',
        'path' => '/integrations/collections/{id}',
        'methodController' => 'CollectionController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
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
            'check.access:permissions,audit.read',
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
            'check.access:permissions,audit.read',
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
            'check.access:permissions,audit.create',
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
            'check.access:permissions,audit.update',
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
            'check.access:permissions,audit.update',
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
            'check.access:permissions,audit.delete',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // data.use.registers integrations
    [
        'name' => 'dur.integrations.get',
        'method' => 'get',
        'path' => '/integrations/dur',
        'methodController' => 'DurController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'dur.integrations.get.id',
        'method' => 'get',
        'path' => '/integrations/dur/{id}',
        'methodController' => 'DurController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'get.integrations.post',
        'method' => 'post',
        'path' => '/integrations/dur',
        'methodController' => 'DurController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'dur.integrations.put.id',
        'method' => 'put',
        'path' => '/integrations/dur/{id}',
        'methodController' => 'DurController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dur.integrations.patch.id',
        'method' => 'patch',
        'path' => '/integrations/dur/{id}',
        'methodController' => 'DurController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
            'sanitize.input',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dur.integrations.delete.id',
        'method' => 'delete',
        'path' => '/integrations/dur/{id}',
        'methodController' => 'DurController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
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
            'check.access:permissions,applications.read',
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
            'check.access:permissions,applications.read',
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
            'check.access:permissions,applications.create',
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
            'check.access:permissions,applications.update',
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
            'check.access:permissions,applications.update',
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
            'check.access:permissions,applications.delete',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:roles,hdruk.superadmin',
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
            'check.access:permissions,permissions.update',
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
            'check.access:permissions,permissions.update',
        ],
        'constraint' => [
            'teamId' => '[0-9]+', 
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'team.user.role.bulk',
        'method' => 'patch',
        'path' => '/teams/{teamId}/roles',
        'methodController' => 'TeamUserController@updateBulk',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,permissions.update',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
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
            'check.access:permissions,permissions.update',
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
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'datasets-count-field',
        'method' => 'get',
        'path' => '/datasets/count/{field}',
        'methodController' => 'DatasetController@count',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'datasets-histogram',
        'method' => 'get',
        'path' => '/datasets/histogram',
        'methodController' => 'DatasetController@histogram',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'datasets',
        'method' => 'get',
        'path' => '/datasets/{id}',
        'methodController' => 'DatasetController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
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
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,datasets.delete',
        ],
        'constraint' => [
            'id', '[0-9]+'
        ],
    ],
    [
        'name' => 'datasets',
        'method' => 'get',
        'path' => '/datasets/export',
        'methodController' => 'DatasetController@export',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [],
    ],
    // datasets integrations
    [
        'name' => 'datasets.integrations',
        'method' => 'get',
        'path' => '/integrations/datasets',
        'methodController' => 'DatasetController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'datasets.integrations',
        'method' => 'get',
        'path' => '/integrations/datasets/{id}',
        'methodController' => 'DatasetController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'datasets.integrations',
        'method' => 'post',
        'path' => '/integrations/datasets',
        'methodController' => 'DatasetController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'datasets.integrations',
        'method' => 'delete',
        'path' => '/integrations/datasets/{id}',
        'methodController' => 'DatasetController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
        ],
        'constraint' => [
            'id', '[0-9]+'
        ],
    ],
    [
        'name' => 'datasets.integrations.test',
        'method' => 'post',
        'path' => '/integrations/datasets/test',
        'methodController' => 'DatasetController@datasetTest',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'integration.auth',
            'sanitize.input',
        ],
        'constraint' => [],
    ],

    // team.notification
    [
        'name' => 'team.notification',
        'method' => 'get',
        'path' => '/teams/{teamId}/notifications',
        'methodController' => 'TeamNotificationController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            // 'check.access:roles,custodian.team.admin',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'team.notification',
        'method' => 'post',
        'path' => '/teams/{teamId}/notifications',
        'methodController' => 'TeamNotificationController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,notifications.update',
            // 'check.access:roles,custodian.team.admin',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
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
        'path' => '/teams/{teamId}/federations/test',
        'methodController' => 'FederationController@testFederation',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],

    // cohort_requests
    [
        'name' => 'cohort_requests',
        'method' => 'get',
        'path' => '/cohort_requests',
        'methodController' => 'CohortRequestController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'cohort_requests',
        'method' => 'get',
        'path' => '/cohort_requests/{id}',
        'methodController' => 'CohortRequestController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'cohort_requests',
        'method' => 'post',
        'path' => '/cohort_requests',
        'methodController' => 'CohortRequestController@store',
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
        'name' => 'cohort_requests',
        'method' => 'put',
        'path' => '/cohort_requests/{id}',
        'methodController' => 'CohortRequestController@update',
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
        'name' => 'cohort_requests',
        'method' => 'delete',
        'path' => '/cohort_requests/{id}',
        'methodController' => 'CohortRequestController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'cohort_requests',
        'method' => 'get',
        'path' => '/cohort_requests/export',
        'methodController' => 'CohortRequestController@export',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,cohort.read',
        ],
        'constraint' => [],
    ],

    // search
    [
        'name' => 'search.datasets',
        'method' => 'post',
        'path' => '/search/datasets',
        'methodController' => 'SearchController@datasets',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => ['jwt.verify'],
        'constraint' => [],
    ],
    [
        'name' => 'search.tools',
        'method' => 'post',
        'path' => '/search/tools',
        'methodController' => 'SearchController@tools',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => ['jwt.verify'],
        'constraint' => [],
    ],
    [
        'name' => 'search.collections',
        'method' => 'post',
        'path' => '/search/collections',
        'methodController' => 'SearchController@collections',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => ['jwt.verify'],
        'constraint' => [],
    ],
    [
        'name' => 'search.data_uses',
        'method' => 'post',
        'path' => '/search/dur',
        'methodController' => 'SearchController@dataUses',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => ['jwt.verify'],
        'constraint' => [],
    ],

// categories
    [
        'name' => 'categories',
        'method' => 'get',
        'path' => '/categories',
        'methodController' => 'CategoryController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'categories',
        'method' => 'get',
        'path' => '/categories/{id}',
        'methodController' => 'CategoryController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'categories',
        'method' => 'post',
        'path' => '/categories',
        'methodController' => 'CategoryController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'categories',
        'method' => 'put',
        'path' => '/categories/{id}',
        'methodController' => 'CategoryController@update',
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
        'name' => 'categories',
        'method' => 'patch',
        'path' => '/categories/{id}',
        'methodController' => 'CategoryController@edit',
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
        'name' => 'categories',
        'method' => 'delete',
        'path' => '/categories/{id}',
        'methodController' => 'CategoryController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // saved searches
    [
        'name' => 'saved_searches',
        'method' => 'get',
        'path' => '/saved_searches',
        'methodController' => 'SavedSearchController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'saved_searches',
        'method' => 'get',
        'path' => '/saved_searches/{id}',
        'methodController' => 'SavedSearchController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'saved_searches',
        'method' => 'post',
        'path' => '/saved_searches',
        'methodController' => 'SavedSearchController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'saved_searches',
        'method' => 'put',
        'path' => '/saved_searches/{id}',
        'methodController' => 'SavedSearchController@update',
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
        'name' => 'saved_searches',
        'method' => 'patch',
        'path' => '/saved_searches/{id}',
        'methodController' => 'SavedSearchController@edit',
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
        'name' => 'saved_searches',
        'method' => 'delete',
        'path' => '/saved_searches/{id}',
        'methodController' => 'SavedSearchController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // keywords
    [
        'name' => 'keywords.get',
        'method' => 'get',
        'path' => '/keywords',
        'methodController' => 'KeywordController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'keywords.get.id',
        'method' => 'get',
        'path' => '/keywords/{id}',
        'methodController' => 'KeywordController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'keywords.post',
        'method' => 'post',
        'path' => '/keywords',
        'methodController' => 'KeywordController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'keywords.put.id',
        'method' => 'put',
        'path' => '/keywords/{id}',
        'methodController' => 'KeywordController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'keywords.patch.id',
        'method' => 'patch',
        'path' => '/keywords/{id}',
        'methodController' => 'KeywordController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'keywords.delete.id',
        'method' => 'delete',
        'path' => '/keywords/{id}',
        'methodController' => 'KeywordController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // data use registers
    [
        'name' => 'dur.get',
        'method' => 'get',
        'path' => '/dur',
        'methodController' => 'DurController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,dur.read',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'dur.get.id',
        'method' => 'get',
        'path' => '/dur/{id}',
        'methodController' => 'DurController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,dur.read',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dur.post',
        'method' => 'post',
        'path' => '/dur',
        'methodController' => 'DurController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,dur.create',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dur.put.id',
        'method' => 'put',
        'path' => '/dur/{id}',
        'methodController' => 'DurController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,dur.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dur.patch.id',
        'method' => 'patch',
        'path' => '/dur/{id}',
        'methodController' => 'DurController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,dur.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dur.delete.id',
        'method' => 'delete',
        'path' => '/dur/{id}',
        'methodController' => 'DurController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,dur.delete',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // organisations
    [
        'name' => 'users.organisations.index',
        'method' => 'get',
        'path' => '/users/organisations',
        'methodController' => 'UserOrganisationController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => ['jwt.verify'],
        'constraint' => [],
    ],


];