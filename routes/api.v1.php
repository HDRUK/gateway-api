<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TestController;
use App\Http\Controllers\Api\V1\RegisterController;
use App\Http\Controllers\Api\V1\TeamUserController;
use App\Http\Controllers\Api\V1\ApplicationController;
use App\Http\Controllers\Api\V1\SocialLoginController;

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
    Route::any('/test', [TestController::class, 'test']);

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
});

// stop all all other routes
Route::any('{path}', function() {
    $response = [
        'message' => 'Resource not found',
    ];

    return response()->json($response, 404);
});