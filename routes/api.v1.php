<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\TestController;
use App\Http\Controllers\Api\V1\RegisterController;
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
    ];

    foreach ($routes as $path => $controller) {
        Route::get('/' . $path, ['as' => $path . '.get.index', 'uses' => $controller . '@index']);
        Route::get('/' . $path . '/{id}', ['as' => $path . '.get.show', 'uses' => $controller . '@show'])->where('id', '[0-9]+');
        Route::post('/' . $path, ['as' => $path . '.post.store', 'uses' => $controller . '@store']);
        Route::patch('/' . $path . '/{id}', ['as' => $path . '.patch.update', 'uses' => $controller . '@update'])->where('id', '[0-9]+');
        Route::delete('/' . $path . '/{id}', ['as' => $path . '.delete.destroy', 'uses' => $controller . '@destroy'])->where('id', '[0-9]+');
    }
});

// stop all all other routes
Route::any('{path}', function() {
    $response = [
        'message' => 'Resource not found',
    ];

    return response()->json($response, 404);
});