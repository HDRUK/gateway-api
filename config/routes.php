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
        'name' => 'api.v1.auth.register',
        'method' => 'post',
        'path' => '/auth',
        'methodController' => 'AuthController@checkAuthorization',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'api.v1.auth.refresh',
        'method' => 'post',
        'path' => '/refresh_token',
        'methodController' => 'AuthController@refreshToken',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
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
            'provider' => 'google|azure|linkedin|openathens',
        ],
    ],
    [
        'name' => 'login.social',
        'method' => 'get',
        'path' => '/auth/dta/{provider}',
        'methodController' => 'SocialLoginController@dtaLogin',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [
            'provider' => 'google|azure|linkedin|openathens',
        ],
    ],
    [
        'name' => 'login.social',
        'method' => 'get',
        'path' => '/auth/dta/{provider}/callback',
        'methodController' => 'SocialLoginController@dtaCallback',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [
            'provider' => 'google|azure|linkedin|openathens',
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
            'provider' => 'google|azure|linkedin|openathens',
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
        'path' => '/integrations/dar',
        'methodController' => 'DarIntegrationController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,dar.read.all',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'dar.integrations',
        'method' => 'get',
        'path' => '/integrations/dar/{id}',
        'methodController' => 'DarIntegrationController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,dar.read.all',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar.integrations',
        'method' => 'put',
        'path' => '/integrations/dar/{id}',
        'methodController' => 'DarIntegrationController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,dar.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar.integrations',
        'method' => 'patch',
        'path' => '/integrations/dar/{id}',
        'methodController' => 'DarIntegrationController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,dar.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'csat',
        'method' => 'post',
        'path' => '/csat',
        'methodController' => 'CustomerSatisfactionController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'csat',
        'method' => 'patch',
        'path' => '/csat/{id}',
        'methodController' => 'CustomerSatisfactionController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'sitemap',
        'method' => 'get',
        'path' => '/sitemap',
        'methodController' => 'SiteMapController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'flags',
        'method' => 'post',
        'path' => '/feature-flags',
        'methodController' => 'FeatureFlagController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'flags',
        'method' => 'get',
        'path' => '/feature-flags/enabled',
        'methodController' => 'FeatureFlagController@getEnabledFeatures',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    // TODO - Add DAR.decision rule and route

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
        'method' => 'get',
        'path' => '/teams/search',
        'methodController' => 'TeamController@searchByName',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'teams',
        'method' => 'get',
        'path' => '/teams/{teamPid}/id',
        'methodController' => 'TeamController@getIdFromPid',
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
        'method' => 'get',
        'path' => '/teams/{teamId}/summary',
        'methodController' => 'TeamController@showSummary',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
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
    [
        'name' => 'teams',
        'method' => 'get',
        'path' => '/teams/{teamId}/datasets',
        'methodController' => 'TeamController@datasets',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
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
        'middleware' => ['sunset'],
        'constraint' => [],
    ],
    [
        'name' => 'tools-count-field',
        'method' => 'get',
        'path' => '/tools/count/{field}',
        'methodController' => 'ToolController@count',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => ['sunset'],
        'constraint' => [],
    ],
    [
        'name' => 'tools',
        'method' => 'get',
        'path' => '/tools/{id}',
        'methodController' => 'ToolController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => ['sunset'],
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
            'sunset'
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
            'sunset'
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
            'sunset'
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
            'sunset'
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
        'methodController' => 'IntegrationToolController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,tools.read',
            'sanitize.input',
            'sunset'
        ],
        'constraint' => [],
    ],
    [
        'name' => 'tools.integrations',
        'method' => 'get',
        'path' => '/integrations/tools/{id}',
        'methodController' => 'IntegrationToolController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,tools.read',
            'sanitize.input',
            'sunset'
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'tools.integrations',
        'method' => 'post',
        'path' => '/integrations/tools',
        'methodController' => 'IntegrationToolController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,tools.create',
            'sanitize.input',
            'sunset'
        ],
        'constraint' => [],
    ],
    [
        'name' => 'tools.integrations',
        'method' => 'put',
        'path' => '/integrations/tools/{id}',
        'methodController' => 'IntegrationToolController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,tools.update',
            'sanitize.input',
            'sunset'
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'tools.integrations',
        'method' => 'patch',
        'path' => '/integrations/tools/{id}',
        'methodController' => 'IntegrationToolController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,tools.update',
            'sanitize.input',
            'sunset'
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'tools.integrations',
        'method' => 'delete',
        'path' => '/integrations/tools/{id}',
        'methodController' => 'IntegrationToolController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,tools.delete',
            'sunset'
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
            'check.access.userId',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    [
        'name' => 'users',
        'method' => 'get',
        'path' => '/users/verify-secondary-email/{uuid}',
        'methodController' => 'UserController@verifySecondaryEmail',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
        ],
        'constraint' => [
                'uuid' => '[0-9a-fA-F\-]{36}',

        ],
    ],

        [
        'name' => 'users',
        'method' => 'post',
        'path' => '/users/{id}/resend-secondary-verification',
        'methodController' => 'UserController@resendSecondaryVerificationEmail',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access.userId',
        ],
        'constraint' => [

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
            'check.access.userId',
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

    // users & roles
    [
        'name' => 'users.roles.post',
        'method' => 'post',
        'path' => '/users/{userId}/roles',
        'methodController' => 'UserRoleController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'users.roles.patch',
        'method' => 'patch',
        'path' => '/users/{userId}/roles',
        'methodController' => 'UserRoleController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'users.roles.delete',
        'method' => 'delete',
        'path' => '/users/{userId}/roles',
        'methodController' => 'UserRoleController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [],
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
        'middleware' => ['sunset'],
        'constraint' => [],
    ],
    [
        'name' => 'count_unique_fields_collections',
        'method' => 'get',
        'path' => '/collections/count/{field}',
        'methodController' => 'CollectionController@count',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => ['sunset'],
        'constraint' => [],
    ],
    [
        'name' => 'collections',
        'method' => 'get',
        'path' => '/collections/{id}',
        'methodController' => 'CollectionController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => ['sunset'],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'post',
        'path' => '/teams/{teamId}/collections',
        'methodController' => 'CollectionController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,collections.create',
            'sunset'
        ],
        'constraint' => [],
    ],
    [
        'name' => 'collections',
        'method' => 'put',
        'path' => '/teams/{teamId}/collections/{id}',
        'methodController' => 'CollectionController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,collections.update',
            'sunset'
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'patch',
        'path' => '/teams/{teamId}/collections/{id}',
        'methodController' => 'CollectionController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,collections.update',
            'sunset'
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections',
        'method' => 'delete',
        'path' => '/teams/{teamId}/collections/{id}',
        'methodController' => 'CollectionController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,collections.delete',
            'sunset'
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
        'methodController' => 'IntegrationCollectionController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,collections.read',
            'sunset'
        ],
        'constraint' => [],
    ],
    [
        'name' => 'collections.integrations',
        'method' => 'get',
        'path' => '/integrations/collections/{id}',
        'methodController' => 'IntegrationCollectionController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,collections.read',
            'sunset'
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections.integrations',
        'method' => 'post',
        'path' => '/integrations/collections',
        'methodController' => 'IntegrationCollectionController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,collections.create',
            'sunset'
        ],
        'constraint' => [],
    ],
    [
        'name' => 'collections.integrations',
        'method' => 'put',
        'path' => '/integrations/collections/{id}',
        'methodController' => 'IntegrationCollectionController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,collections.update',
            'sunset'
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections.integrations',
        'method' => 'patch',
        'path' => '/integrations/collections/{id}',
        'methodController' => 'IntegrationCollectionController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,collections.update',
            'sunset'
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'collections.integrations',
        'method' => 'delete',
        'path' => '/integrations/collections/{id}',
        'methodController' => 'IntegrationCollectionController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,collections.delete',
            'sunset'
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
        'methodController' => 'IntegrationDurController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,dur.read',
            'sunset'
        ],
        'constraint' => [],
    ],
    [
        'name' => 'dur.integrations.get.id',
        'method' => 'get',
        'path' => '/integrations/dur/{id}',
        'methodController' => 'IntegrationDurController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,dur.read',
            'sunset'
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dur.integrations.post',
        'method' => 'post',
        'path' => '/integrations/dur',
        'methodController' => 'IntegrationDurController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,dur.create',
            'sanitize.input',
            'sunset'
        ],
        'constraint' => [],
    ],
    [
        'name' => 'dur.integrations.put.id',
        'method' => 'put',
        'path' => '/integrations/dur/{id}',
        'methodController' => 'IntegrationDurController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,dur.update',
            'sanitize.input',
            'sunset'
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dur.integrations.patch.id',
        'method' => 'patch',
        'path' => '/integrations/dur/{id}',
        'methodController' => 'IntegrationDurController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,dur.update',
            'sanitize.input',
            'sunset'
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dur.integrations.delete.id',
        'method' => 'delete',
        'path' => '/integrations/dur/{id}',
        'methodController' => 'IntegrationDurController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,dur.delete',
            'sunset'
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
    [
        'name' => 'applications',
        'method' => 'patch',
        'path' => '/applications/{id}/clientid',
        'methodController' => 'ApplicationController@generateClientIdById',
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
            'check.access:permissions,team-members.create',
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
            'check.access:permissions,team-members.update',
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
            'check.access:permissions,team-members.update',
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
            'check.access:permissions,team-members.delete',
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
        'middleware' => ['sunset'],
        'constraint' => [],
    ],
    [
        'name' => 'datasets-count-field',
        'method' => 'get',
        'path' => '/datasets/count/{field}',
        'methodController' => 'DatasetController@count',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => ['sunset'],
        'constraint' => [],
    ],
    [
        'name' => 'datasets',
        'method' => 'get',
        'path' => '/datasets/{id}',
        'methodController' => 'DatasetController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => ['sunset'],
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
            'sunset'
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
            //'sanitize.input',
            'check.access:permissions,datasets.update',
            'sunset'
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
            'sunset'
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
            'sunset'
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
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'datasets',
        'method' => 'get',
        'path' => '/datasets/export_metadata/{id}',
        'methodController' => 'DatasetController@exportMetadata',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'datasets',
        'method' => 'get',
        'path' => '/datasets/export/mock',
        'methodController' => 'DatasetController@exportMock',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'datasets',
        'method' => 'post',
        'path' => '/datasets/admin_ctrl/trigger/term_extraction',
        'methodController' => 'AdminDatasetController@triggerTermExtraction',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'datasets',
        'method' => 'post',
        'path' => '/datasets/admin_ctrl/trigger/linkage_extraction',
        'methodController' => 'AdminDatasetController@triggerLinkageExtraction',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [],
    ],


    // datasets integrations
    [
        'name' => 'datasets.integrations',
        'method' => 'get',
        'path' => '/integrations/datasets',
        'methodController' => 'IntegrationDatasetController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,datasets.read',
            'sunset'
        ],
        'constraint' => [],
    ],
    [
        'name' => 'datasets.integrations',
        'method' => 'get',
        'path' => '/integrations/datasets/{id}',
        'methodController' => 'IntegrationDatasetController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,datasets.read',
            'sunset'
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'datasets.integrations',
        'method' => 'post',
        'path' => '/integrations/datasets',
        'methodController' => 'IntegrationDatasetController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,datasets.create',
            'sanitize.input',
            'sunset'
        ],
        'constraint' => [],
    ],
    [
        'name' => 'datasets.integrations',
        'method' => 'put',
        'path' => '/integrations/datasets/{id}',
        'methodController' => 'IntegrationDatasetController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,datasets.update',
            'sanitize.input',
            'sunset'
        ],
        'constraint' => [],
    ],
    [
        'name' => 'datasets.integrations',
        'method' => 'delete',
        'path' => '/integrations/datasets/{id}',
        'methodController' => 'IntegrationDatasetController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,datasets.delete',
            'sunset'
        ],
        'constraint' => [
            'id', '[0-9]+'
        ],
    ],
    [
        'name' => 'datasets.integrations.test',
        'method' => 'post',
        'path' => '/integrations/datasets/test',
        'methodController' => 'IntegrationDatasetController@datasetTest',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'sunset'
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
            'check.access:permissions,cohort.read',
        ],
        'constraint' => [],
    ],
    [
        // User needs access - used in button (many places) and form - so this is handled inside the endpoint
        'name' => 'cohort_requests_user',
        'method' => 'get',
        'path' => '/cohort_requests/user/{id}',
        'methodController' => 'CohortRequestController@byUser',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        // User needs access - used in button (many places) and form - so this is handled inside the endpoint
        'name' => 'cohort_requests_request_nhse_access',
        'method' => 'post',
        'path' => '/cohort_requests/user/{id}/request_nhse_access',
        'methodController' => 'CohortRequestController@requestNhseAccess',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        // User needs access - used in button (many places) and form - so this is handled inside the endpoint
        'name' => 'cohort_requests_indicate_nhse_access',
        'method' => 'post',
        'path' => '/cohort_requests/user/{id}/indicate_nhse_access',
        'methodController' => 'CohortRequestController@indicateNhseAccess',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'cohort_requests_access',
        'method' => 'get',
        'path' => '/cohort_requests/access',
        'methodController' => 'CohortRequestController@checkAccess',
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
            'check.access:permissions,cohort.read',
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
            'check.access:permissions,cohort.update',
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
            'check.access:permissions,cohort.delete',
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
    [
        'name' => 'cohort_requests',
        'method' => 'post',
        'path' => '/cohort_requests/{id}/admin',
        'methodController' => 'CohortRequestController@assignAdminPermission',
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
        'name' => 'cohort_requests',
        'method' => 'delete',
        'path' => '/cohort_requests/{id}/admin',
        'methodController' => 'CohortRequestController@removeAdminPermission',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // search
    [
        'name' => 'search.datasets',
        'method' => 'post',
        'path' => '/search/datasets',
        'methodController' => 'SearchController@datasets',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'search.similarDatasets',
        'method' => 'post',
        'path' => '/search/similar/datasets',
        'methodController' => 'SearchController@similarDatasets',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'search.tools',
        'method' => 'post',
        'path' => '/search/tools',
        'methodController' => 'SearchController@tools',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'search.collections',
        'method' => 'post',
        'path' => '/search/collections',
        'methodController' => 'SearchController@collections',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'search.data_uses',
        'method' => 'post',
        'path' => '/search/dur',
        'methodController' => 'SearchController@dataUses',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'search.publications',
        'method' => 'post',
        'path' => '/search/publications',
        'methodController' => 'SearchController@publications',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'search.doiSearch',
        'method' => 'post',
        'path' => '/search/doi',
        'methodController' => 'SearchController@doiSearch',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'search.data_custodian_networks',
        'method' => 'post',
        'path' => '/search/data_custodian_networks',
        'methodController' => 'SearchController@dataCustodianNetworks',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'search.data_custodians',
        'method' => 'post',
        'path' => '/search/data_custodians',
        'methodController' => 'SearchController@dataCustodians',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
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
        'name' => 'dur.export',
        'method' => 'get',
        'path' => '/dur/export',
        'methodController' => 'DurController@export',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'dur.get',
        'method' => 'get',
        'path' => '/dur',
        'methodController' => 'DurController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => ['sunset'],
        'constraint' => [],
    ],
    [
        'name' => 'dur.count-field',
        'method' => 'get',
        'path' => '/dur/count/{field}',
        'methodController' => 'DurController@count',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => ['sunset'],
        'constraint' => [],
    ],
    [
        'name' => 'dur.get.id',
        'method' => 'get',
        'path' => '/dur/{id}',
        'methodController' => 'DurController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => ['sunset'],
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
            'sunset'
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
            'sunset'
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
            'sunset'
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
            'sunset'
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dur.post.upload',
        'method' => 'post',
        'path' => '/dur/upload',
        'methodController' => 'DurController@upload',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'dur.get.template',
        'method' => 'get',
        'path' => '/dur/template',
        'methodController' => 'DurController@exportTemplate',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
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

    // publications
    [
        'name' => 'publications.index',
        'method' => 'get',
        'path' => 'publications',
        'methodController' => 'PublicationController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => ['sunset'],
        'constraint' => [],
    ],
    [
        'name' => 'publications-count-field',
        'method' => 'get',
        'path' => '/publications/count/{field}',
        'methodController' => 'PublicationController@count',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => ['sunset'],
        'constraint' => [],
    ],
    [
        'name' => 'publications.show',
        'method' => 'get',
        'path' => 'publications/{id}',
        'methodController' => 'PublicationController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => ['sunset'],
        'constraint' => [],
    ],
    [
        'name' => 'publications.create',
        'method' => 'post',
        'path' => 'publications',
        'methodController' => 'PublicationController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,papers.create',
            'sunset'
        ],
        'constraint' => [
        ],
    ],
    [
        'name' => 'publications.edit',
        'method' => 'patch',
        'path' => 'publications/{id}',
        'methodController' => 'PublicationController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,papers.update',
            'sunset'
        ],
        'constraint' => [],
    ],
    [
        'name' => 'publications.update',
        'method' => 'put',
        'path' => 'publications/{id}',
        'methodController' => 'PublicationController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,papers.update',
            'sunset'
        ],
        'constraint' => [],
    ],
    [
        'name' => 'publications.destroy',
        'method' => 'delete',
        'path' => 'publications/{id}',
        'methodController' => 'PublicationController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,papers.delete',
            'sunset'
        ],
        'constraint' => [],
    ],

    // form hydration
    [
        'name' => 'form_hydration.schema',
        'method' => 'get',
        'path' => '/form_hydration/schema',
        'methodController' => 'FormHydrationController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],

    // programming languages
    [
        'name' => 'programming_languages',
        'method' => 'get',
        'path' => '/programming_languages',
        'methodController' => 'ProgrammingLanguageController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'programming_languages',
        'method' => 'get',
        'path' => '/programming_languages/{id}',
        'methodController' => 'ProgrammingLanguageController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'programming_languages',
        'method' => 'post',
        'path' => '/programming_languages',
        'methodController' => 'ProgrammingLanguageController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'programming_languages',
        'method' => 'put',
        'path' => '/programming_languages/{id}',
        'methodController' => 'ProgrammingLanguageController@update',
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
        'name' => 'programming_languages',
        'method' => 'patch',
        'path' => '/programming_languages/{id}',
        'methodController' => 'ProgrammingLanguageController@edit',
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
        'name' => 'programming_languages',
        'method' => 'delete',
        'path' => '/programming_languages/{id}',
        'methodController' => 'ProgrammingLanguageController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // programming packages
    [
        'name' => 'programming_packages',
        'method' => 'get',
        'path' => '/programming_packages',
        'methodController' => 'ProgrammingPackageController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'programming_packages',
        'method' => 'get',
        'path' => '/programming_packages/{id}',
        'methodController' => 'ProgrammingPackageController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'programming_packages',
        'method' => 'post',
        'path' => '/programming_packages',
        'methodController' => 'ProgrammingPackageController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'programming_packages',
        'method' => 'put',
        'path' => '/programming_packages/{id}',
        'methodController' => 'ProgrammingPackageController@update',
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
        'name' => 'programming_packages',
        'method' => 'patch',
        'path' => '/programming_packages/{id}',
        'methodController' => 'ProgrammingPackageController@edit',
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
        'name' => 'programming_packages',
        'method' => 'delete',
        'path' => '/programming_packages/{id}',
        'methodController' => 'ProgrammingPackageController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // tool type categories
    [
        'name' => 'type_categories',
        'method' => 'get',
        'path' => '/type_categories',
        'methodController' => 'TypeCategoryController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'type_categories',
        'method' => 'get',
        'path' => '/type_categories/{id}',
        'methodController' => 'TypeCategoryController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'type_categories',
        'method' => 'post',
        'path' => '/type_categories',
        'methodController' => 'TypeCategoryController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'type_categories',
        'method' => 'put',
        'path' => '/type_categories/{id}',
        'methodController' => 'TypeCategoryController@update',
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
        'name' => 'type_categories',
        'method' => 'patch',
        'path' => '/type_categories/{id}',
        'methodController' => 'TypeCategoryController@edit',
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
        'name' => 'type_categories',
        'method' => 'delete',
        'path' => '/type_categories/{id}',
        'methodController' => 'TypeCategoryController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // DataProviders
    [
        'name' => 'data_provider_colls',
        'method' => 'get',
        'path' => '/data_provider_colls',
        'methodController' => 'DataProviderCollController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'data_provider_colls',
        'method' => 'get',
        'path' => '/data_provider_colls/{id}',
        'methodController' => 'DataProviderCollController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'data_provider_colls_summary',
        'method' => 'get',
        'path' => '/data_provider_colls/{id}/summary',
        'methodController' => 'DataProviderCollController@showSummary',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'data_provider_colls',
        'method' => 'post',
        'path' => '/data_provider_colls',
        'methodController' => 'DataProviderCollController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'data_provider_colls',
        'method' => 'put',
        'path' => '/data_provider_colls/{id}',
        'methodController' => 'DataProviderCollController@update',
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
        'name' => 'data_provider_colls',
        'method' => 'patch',
        'path' => '/data_provider_colls/{id}',
        'methodController' => 'DataProviderCollController@edit',
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
        'name' => 'data_provider_colls',
        'method' => 'delete',
        'path' => '/data_provider_colls/{id}',
        'methodController' => 'DataProviderCollController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // Form hydration - metadata onboarding
    [
        'name' => 'form_hydration',
        'method' => 'get',
        'path' => '/form_hydration',
        'methodController' => 'FormHydrationController@onboardingFormHydration',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'sanitize.input',
        ],
        'constraint' => [],
    ],

    // licences
    [
        'name' => 'licences',
        'method' => 'get',
        'path' => '/licenses',
        'methodController' => 'LicenseController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [],
    ],
    [
        'name' => 'licences',
        'method' => 'get',
        'path' => '/licenses/{id}',
        'methodController' => 'LicenseController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'licenses',
        'method' => 'post',
        'path' => '/licenses',
        'methodController' => 'LicenseController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'licences',
        'method' => 'put',
        'path' => '/licenses/{id}',
        'methodController' => 'LicenseController@update',
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
        'name' => 'licenses',
        'method' => 'patch',
        'path' => '/licenses/{id}',
        'methodController' => 'LicenseController@edit',
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
        'name' => 'licenses',
        'method' => 'delete',
        'path' => '/licenses/{id}',
        'methodController' => 'LicenseController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // uploads
    [
        'name' => 'uploads',
        'method' => 'post',
        'path' => '/files',
        'methodController' => 'UploadController@upload',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'uploads',
        'method' => 'get',
        'path' => '/files/{id}',
        'methodController' => 'UploadController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'uploads',
        'method' => 'get',
        'path' => '/files/processed/{id}',
        'methodController' => 'UploadController@content',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [],
    ],

    // libraries
    [
        'name' => 'libraries',
        'method' => 'get',
        'path' => '/libraries',
        'methodController' => 'LibraryController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'libraries',
        'method' => 'get',
        'path' => '/libraries/{id}',
        'methodController' => 'LibraryController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'libraries',
        'method' => 'post',
        'path' => '/libraries',
        'methodController' => 'LibraryController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'libraries',
        'method' => 'put',
        'path' => '/libraries/{id}',
        'methodController' => 'LibraryController@update',
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
        'name' => 'libraries',
        'method' => 'patch',
        'path' => '/libraries/{id}',
        'methodController' => 'LibraryController@edit',
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
        'name' => 'libraries',
        'method' => 'delete',
        'path' => '/libraries/{id}',
        'methodController' => 'LibraryController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    // Enquiry Threads
    [
        'name' => 'enquiry_threads',
        'method' => 'get',
        'path' => '/enquiry_threads',
        'methodController' => 'EnquiryThreadController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'enquiry_threads',
        'method' => 'get',
        'path' => '/enquiry_threads/{id}',
        'methodController' => 'EnquiryThreadController@show',
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
        'name' => 'enquiry_threads',
        'method' => 'post',
        'path' => '/enquiry_threads',
        'methodController' => 'EnquiryThreadController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'admin_ctrl',
        'method' => 'post',
        'path' => '/admin_ctrl/trigger_ted',
        'methodController' => 'AdminPanelController@triggerTermExtractionDirector',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'sanitize.input',
        ],
        'constraint' => [],
    ],

    // questions
    [
        'name' => 'questions',
        'method' => 'get',
        'path' => '/questions',
        'methodController' => 'QuestionBankController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,question-bank.read',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'questions',
        'method' => 'get',
        'path' => '/questions/standard',
        'methodController' => 'QuestionBankController@indexStandard',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,question-bank.read',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'questions',
        'method' => 'get',
        'path' => '/questions/custom',
        'methodController' => 'QuestionBankController@indexCustom',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,question-bank.read',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'questions',
        'method' => 'get',
        'path' => '/questions/archived',
        'methodController' => 'QuestionBankController@indexArchived',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,question-bank.read',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'questions',
        'method' => 'get',
        'path' => '/teams/{teamId}/questions/section/{sectionId}',
        'methodController' => 'TeamQuestionBankController@indexBySection',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,question-bank.read',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'questions',
        'method' => 'get',
        'path' => '/questions/{id}',
        'methodController' => 'QuestionBankController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,question-bank.read',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'questions',
        'method' => 'get',
        'path' => '/questions/version/{id}',
        'methodController' => 'QuestionBankController@showVersion',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,question-bank.read',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'questions',
        'method' => 'post',
        'path' => '/questions',
        'methodController' => 'QuestionBankController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,question-bank.create',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'questions',
        'method' => 'put',
        'path' => '/questions/{id}',
        'methodController' => 'QuestionBankController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,question-bank.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'questions',
        'method' => 'patch',
        'path' => '/questions/{id}',
        'methodController' => 'QuestionBankController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,question-bank.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'questions',
        'method' => 'patch',
        'path' => '/questions/{id}/{status}',
        'methodController' => 'QuestionBankController@updateStatus',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,question-bank.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'questions',
        'method' => 'delete',
        'path' => '/questions/{id}',
        'methodController' => 'QuestionBankController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,question-bank.delete',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // dar/applications
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => '/teams/{teamId}/dar/applications',
        'methodController' => 'TeamDataAccessApplicationController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-applications.provider.read',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => 'teams/{teamId}/dar/applications',
        'methodController' => 'TeamDataAccessApplicationController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-applications.provider.read',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => 'users/{userId}/dar/applications',
        'methodController' => 'UserDataAccessApplicationController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => 'teams/{teamId}/dar/applications/count/{field}',
        'methodController' => 'TeamDataAccessApplicationController@count',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-applications.provider.read',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => 'teams/{teamId}/dar/applications/count',
        'methodController' => 'TeamDataAccessApplicationController@allCounts',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-applications.provider.read',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => 'users/{userId}/dar/applications/count/{field}',
        'methodController' => 'UserDataAccessApplicationController@count',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => 'users/{userId}/dar/applications/count/',
        'methodController' => 'UserDataAccessApplicationController@allCounts',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => '/teams/{teamId}/dar/applications/{id}',
        'methodController' => 'TeamDataAccessApplicationController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-applications.provider.read',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => '/users/{userId}/dar/applications/{id}',
        'methodController' => 'UserDataAccessApplicationController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => '/teams/{teamId}/dar/applications/{id}/files',
        'methodController' => 'TeamDataAccessApplicationController@showFiles',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-applications.provider.read',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => '/teams/{teamId}/dar/applications/{id}/downloadCsv',
        'methodController' => 'TeamDataAccessApplicationController@downloadCsv',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-applications.provider.read',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'fileId' => '[0-9]+',
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => '/teams/{teamId}/dar/applications/{id}/files/{fileId}/download',
        'methodController' => 'TeamDataAccessApplicationController@downloadFile',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-applications.provider.read',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'fileId' => '[0-9]+',
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => '/teams/{teamId}/dar/applications/{id}/files/downloadAll',
        'methodController' => 'TeamDataAccessApplicationController@downloadAllFiles',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-applications.provider.read',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => 'users/{userId}/dar/applications/{id}/files',
        'methodController' => 'UserDataAccessApplicationController@showFiles',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => '/users/{userId}/dar/applications/{id}/files/{fileId}/download',
        'methodController' => 'UserDataAccessApplicationController@downloadFile',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'fileId' => '[0-9]+',
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => '/teams/{teamId}/dar/applications/{id}/answers',
        'methodController' => 'TeamDataAccessApplicationController@showAnswers',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-applications.provider.read',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => 'users/{userId}/dar/applications/{id}/answers',
        'methodController' => 'UserDataAccessApplicationController@showAnswers',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => '/teams/{teamId}/dar/applications/{id}/status',
        'methodController' => 'TeamDataAccessApplicationController@status',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-applications.status.read',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'post',
        'path' => '/dar/applications',
        'methodController' => 'DataAccessApplicationController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'put',
        'path' => '/users/{userId}/dar/applications/{id}/answers',
        'methodController' => 'UserDataAccessApplicationController@storeAnswers',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
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
        'name' => 'dar/applications',
        'method' => 'put',
        'path' => '/teams/{teamId}/dar/applications/{id}',
        'methodController' => 'TeamDataAccessApplicationController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,data-access-applications.provider.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'put',
        'path' => 'users/{userId}/dar/applications/{id}',
        'methodController' => 'UserDataAccessApplicationController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
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
        'name' => 'dar/applications',
        'method' => 'patch',
        'path' => '/teams/{teamId}/dar/applications/{id}',
        'methodController' => 'TeamDataAccessApplicationController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,data-access-applications.provider.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'patch',
        'path' => 'users/{userId}/dar/applications/{id}',
        'methodController' => 'UserDataAccessApplicationController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
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
        'name' => 'dar/applications',
        'method' => 'delete',
        'path' => '/dar/applications/{id}',
        'methodController' => 'DataAccessApplicationController@destroy',
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
        'name' => 'dar/applications',
        'method' => 'delete',
        'path' => '/dar/applications/{id}/files/{fileId}',
        'methodController' => 'DataAccessApplicationController@destroyFile',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'fileId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'delete',
        'path' => '/users/{userId}/dar/applications/{id}/files/{fileId}',
        'methodController' => 'UserDataAccessApplicationController@destroyFile',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'fileId' => '[0-9]+',
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'delete',
        'path' => '/users/{userId}/dar/applications/{id}',
        'methodController' => 'UserDataAccessApplicationController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'userId' => '[0-9]+',
        ],
    ],

    // dar/applications/reviews
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => '/teams/{teamId}/dar/applications/{id}/reviews',
        'methodController' => 'DataAccessApplicationReviewController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-applications.review.read',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => '/users/{userId}/dar/applications/{id}/reviews',
        'methodController' => 'DataAccessApplicationReviewController@indexUser',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'userId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => '/teams/{teamId}/dar/applications/{id}/reviews/{reviewId}/download/{fileId}',
        'methodController' => 'DataAccessApplicationReviewController@downloadFile',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-applications.review.read',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
            'reviewId' => '[0-9]+',
            'fileId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'get',
        'path' => '/users/{userId}/dar/applications/{id}/reviews/{reviewId}/download/{fileId}',
        'methodController' => 'DataAccessApplicationReviewController@downloadUserFile',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'userId' => '[0-9]+',
            'reviewId' => '[0-9]+',
            'fileId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'post',
        'path' => '/teams/{teamId}/dar/applications/{id}/questions/{questionId}/reviews',
        'methodController' => 'DataAccessApplicationReviewController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-applications.review.create',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
            'questionId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'post',
        'path' => '/teams/{teamId}/dar/applications/{id}/reviews',
        'methodController' => 'DataAccessApplicationReviewController@storeGlobal',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-applications.review.create',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'put',
        'path' => '/teams/{teamId}/dar/applications/{id}/questions/{questionId}/reviews/{reviewId}',
        'methodController' => 'DataAccessApplicationReviewController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-applications.review.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
            'questionId' => '[0-9]+',
            'reviewId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'put',
        'path' => '/teams/{teamId}/dar/applications/{id}/reviews/{reviewId}',
        'methodController' => 'DataAccessApplicationReviewController@updateGlobal',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-applications.review.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
            'reviewId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'put',
        'path' => '/users/{userId}/dar/applications/{id}/questions/{questionId}/reviews/{reviewId}',
        'methodController' => 'DataAccessApplicationReviewController@userUpdate',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'userId' => '[0-9]+',
            'id' => '[0-9]+',
            'questionId' => '[0-9]+',
            'reviewId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'put',
        'path' => '/users/{userId}/dar/applications/{id}/reviews/{reviewId}',
        'methodController' => 'DataAccessApplicationReviewController@userUpdateGlobal',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'userId' => '[0-9]+',
            'id' => '[0-9]+',
            'reviewId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'delete',
        'path' => '/teams/{teamId}/dar/applications/{id}/questions/{questionId}/reviews/{reviewId}',
        'methodController' => 'DataAccessApplicationReviewController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
            'questionId' => '[0-9]+',
            'reviewId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'delete',
        'path' => '/teams/{teamId}/dar/applications/{id}/reviews/{reviewId}',
        'methodController' => 'DataAccessApplicationReviewController@destroyGlobal',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:roles,hdruk.superadmin',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
            'reviewId' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/applications',
        'method' => 'delete',
        'path' => '/teams/{teamId}/dar/applications/{id}/reviews/{reviewId}/files/{fileId}',
        'methodController' => 'DataAccessApplicationReviewController@destroyFile',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-applications.review.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
            'teamId' => '[0-9]+',
            'reviewId' => '[0-9]+',
            'fileId' => '[0-9]+',
        ],
    ],

    // dar/templates
    [
        'name' => 'dar/templates',
        'method' => 'get',
        'path' => '/dar/templates',
        'methodController' => 'DataAccessTemplateController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-template.read',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'dar/templates',
        'method' => 'get',
        'path' => '/dar/templates/count/{field}',
        'methodController' => 'DataAccessTemplateController@count',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-template.read',
        ],
        'constraint' => [
            'field' => 'published|locked|template_type',
        ],
    ],
    [
        'name' => 'dar/templates',
        'method' => 'get',
        'path' => '/teams/{teamId}/dar/templates',
        'methodController' => 'TeamDataAccessTemplateController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-template.read',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'dar/templates',
        'method' => 'get',
        'path' => '/teams/{teamId}/dar/templates/count/{field}',
        'methodController' => 'TeamDataAccessTemplateController@count',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-template.read',
        ],
        'constraint' => [
            'teamId' => '[0-9]+',
            'field' => 'published|locked|template_type',
        ],
    ],
    [
        'name' => 'dar/templates',
        'method' => 'get',
        'path' => '/dar/templates/{id}',
        'methodController' => 'DataAccessTemplateController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-template.read',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/templates',
        'method' => 'get',
        'path' => '/dar/templates/{id}/download',
        'methodController' => 'DataAccessTemplateController@downloadFile',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/templates',
        'method' => 'post',
        'path' => '/dar/templates',
        'methodController' => 'DataAccessTemplateController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,data-access-template.create',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'dar/templates',
        'method' => 'put',
        'path' => '/dar/templates/{id}',
        'methodController' => 'DataAccessTemplateController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,data-access-template.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/templates',
        'method' => 'patch',
        'path' => '/dar/templates/{id}',
        'methodController' => 'DataAccessTemplateController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,data-access-template.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/templates',
        'method' => 'delete',
        'path' => '/dar/templates/{id}',
        'methodController' => 'DataAccessTemplateController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,data-access-template.delete',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
        [
            'name' => 'dar/templates',
            'method' => 'delete',
            'path' => 'teams/{teamId}/dar/templates/{id}/files/{fileId}',
            'methodController' => 'TeamDataAccessTemplateController@destroyFile',
            'namespaceController' => 'App\Http\Controllers\Api\V1',
            'middleware' => [
                'jwt.verify',
                'check.access:permissions,data-access-template.delete',
            ],
            'constraint' => [
                'id' => '[0-9]+',
                'teamId' => '[0-9]+',
                'fileId' => '[0-9]+',
            ],
        ]
    ],

    // dar/sections
    [
        'name' => 'dar/sections',
        'method' => 'get',
        'path' => '/dar/sections',
        'methodController' => 'DataAccessSectionController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'dar/sections',
        'method' => 'get',
        'path' => '/dar/sections/{id}',
        'methodController' => 'DataAccessSectionController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/sections',
        'method' => 'post',
        'path' => '/dar/sections',
        'methodController' => 'DataAccessSectionController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,question-bank.create',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'dar/sections',
        'method' => 'put',
        'path' => '/dar/sections/{id}',
        'methodController' => 'DataAccessSectionController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,question-bank.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/sections',
        'method' => 'patch',
        'path' => '/dar/sections/{id}',
        'methodController' => 'DataAccessSectionController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'sanitize.input',
            'check.access:permissions,question-bank.update',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'dar/sections',
        'method' => 'delete',
        'path' => '/dar/sections/{id}',
        'methodController' => 'DataAccessSectionController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
            'check.access:permissions,question-bank.delete',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],

    // aliases
    [
        'name' => 'aliases',
        'method' => 'get',
        'path' => '/aliases',
        'methodController' => 'AliasController@index',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'aliases',
        'method' => 'get',
        'path' => '/aliases/{id}',
        'methodController' => 'AliasController@show',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'aliases',
        'method' => 'post',
        'path' => '/aliases',
        'methodController' => 'AliasController@store',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [],
    ],
    [
        'name' => 'aliases',
        'method' => 'put',
        'path' => '/aliases/{id}',
        'methodController' => 'AliasController@update',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'aliases',
        'method' => 'patch',
        'path' => '/aliases/{id}',
        'methodController' => 'AliasController@edit',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
    [
        'name' => 'aliases',
        'method' => 'delete',
        'path' => '/aliases/{id}',
        'methodController' => 'AliasController@destroy',
        'namespaceController' => 'App\Http\Controllers\Api\V1',
        'middleware' => [
            'jwt.verify',
        ],
        'constraint' => [
            'id' => '[0-9]+',
        ],
    ],
];
