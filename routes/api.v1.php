<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TestController;
use App\Http\Controllers\Api\V1\DatasetController;
use App\Http\Controllers\Api\V1\RegisterController;
use App\Http\Controllers\Api\V1\TeamUserController;
use App\Http\Controllers\Api\V1\FederationController;
use App\Http\Controllers\Api\V1\SocialLoginController;
use App\Http\Controllers\Api\V1\TeamNotificationController;

Route::get('/test', function() {
    return Response::json([
        'message' => 'lorem ipsum dolor sit amet, consectetur adip',
    ]);
});

Route::post('/register', [RegisterController::class, 'create']);
Route::post('/auth', [AuthController::class, 'checkAuthorization']);

// login for:  google || linkedin || azure
Route::get('/auth/{provider}', [SocialLoginController::class, 'login'])->where('provider', 'google|linkedin|azure');
Route::get('/auth/{provider}/callback', [SocialLoginController::class, 'callback'])->where('provider', 'google|linkedin|azure');

Route::group(['namespace' => 'App\Http\Controllers\Api\V1', 'middleware' => ['jwt.verify', 'sanitize.input']], function() {
    // Route::any('/test', [TestController::class, 'test']);
    // Route::any('/test/check_access', [TestController::class, 'testCheckRoles'])->middleware(['check.access:roles,reviewer|custodian.team.admin']);
    // Route::any('/test/check_access', [TestController::class, 'testCheckAccess'])->middleware(['check.access:permissions,datasets.read|dur.read|filters.read']);

    $routes = [
        'tags' => 'TagController',
        'features' => 'FeatureController',
        'filters' => 'FilterController',
        'dar-integrations' => 'DarIntegrationController',
        'teams' => 'TeamController',
        'tools' => 'ToolController',
        'activity_logs' => 'ActivityLogController',
        'activity_log_types' => 'ActivityLogTypeController',
        'activity_log_user_types' => 'ActivityLogUserTypeController',
        'permissions' => 'PermissionController',
        'users' => 'UserController',
        'notifications' => 'NotificationController',
        'reviews' => 'ReviewController',
        'sectors' => 'SectorController',
        'collections' => 'CollectionController',
        'audit_logs' => 'AuditLogController',
        'data_use_registers' => 'DataUseRegisterController',
        'applications' => 'ApplicationController',
        'roles' => 'RoleController',
        'emailtemplates' => 'EmailTemplateController',
    ];

    foreach ($routes as $path => $controller) {
        Route::get('/' . $path, ['as' => $path . '.get.index', 'uses' => $controller . '@index']);
        Route::get('/' . $path . '/{id}', ['as' => $path . '.get.show', 'uses' => $controller . '@show'])->where('id', '[0-9]+');
        Route::post('/' . $path, ['as' => $path . '.post.store', 'uses' => $controller . '@store']);
        Route::put('/' . $path . '/{id}', ['as' => $path . '.put.update', 'uses' => $controller . '@update'])->where('id', '[0-9]+');
        Route::patch('/' . $path . '/{id}', ['as' => $path . '.patch.update', 'uses' => $controller . '@edit'])->where('id', '[0-9]+');
        Route::delete('/' . $path . '/{id}', ['as' => $path . '.delete.destroy', 'uses' => $controller . '@destroy'])->where('id', '[0-9]+');
    }

    Route::post('/teams/{teamId}/users', [TeamUserController::class, 'store'])->where('teamId', '[0-9]+');
    Route::put('/teams/{teamId}/users/{userId}', [TeamUserController::class, 'update'])->where(['teamId' => '[0-9]+', 'userId' => '[0-9]+']);
    Route::delete('/teams/{teamId}/users/{userId}', [TeamUserController::class, 'destroy'])->where(['teamId' => '[0-9]+', 'userId' => '[0-9]+']);
    
    Route::post('/dispatch_email', 'EmailController@dispatchEmail');
    Route::post('/logout', 'LogoutController@logout');

    Route::get('/datasets', [DatasetController::class, 'index']);
    Route::get('/datasets/{id}', [DatasetController::class, 'show'])->where('id', '[0-9]+');
    Route::post('/datasets', [DatasetController::class, 'store']);
    Route::delete('/datasets/{id}', [DatasetController::class, 'destroy'])->where('id', '[0-9]+');

    // team - notifications
    Route::post('/teams/{teamId}/notifications', [TeamNotificationController::class, 'storeTeamNotification'])
        ->where('teamId', '[0-9]+')
        ->middleware(['check.access:roles,custodian.team.admin']);
    Route::put('/teams/{teamId}/notifications/{notificationId}', [TeamNotificationController::class, 'updateTeamNotification'])
        ->where(['teamId' => '[0-9]+', 'notificationId' => '[0-9]+'])
        ->middleware(['check.access:roles,custodian.team.admin']);
    Route::delete('/teams/{teamId}/notifications/{notificationId}', [TeamNotificationController::class, 'destroyTeamNotification'])
        ->where(['teamId' => '[0-9]+', 'notificationId' => '[0-9]+'])
        ->middleware(['check.access:roles,custodian.team.admin']);

    // teams - federation
    Route::get('/teams/{teamId}/federations', [FederationController::class, 'index'])
        ->where('teamId', '[0-9]+');
    Route::get('/teams/{teamId}/federations/{federationId}', [FederationController::class, 'show'])
        ->where(['teamId' => '[0-9]+', 'federationId' => '[0-9]+']);
    Route::post('/teams/{teamId}/federations', [FederationController::class, 'store'])->where('teamId', '[0-9]+');
    Route::put('/teams/{teamId}/federations/{federationId}', [FederationController::class, 'update'])
        ->where(['teamId' => '[0-9]+', 'federationId' => '[0-9]+']);
    Route::patch('/teams/{teamId}/federations/{federationId}', [FederationController::class, 'edit'])
        ->where(['teamId' => '[0-9]+', 'federationId' => '[0-9]+']);
    Route::delete('/teams/{teamId}/federations/{federationId}', [FederationController::class, 'delete'])
        ->where(['teamId' => '[0-9]+', 'federationId' => '[0-9]+']);
});

// stop all all other routes
Route::any('{path}', function() {
    $response = [
        'message' => 'Resource not found',
    ];

    return response()->json($response, 404);
});