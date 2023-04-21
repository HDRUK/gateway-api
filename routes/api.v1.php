<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TestController;
use App\Http\Controllers\Api\V1\ToolController;
use App\Http\Controllers\Api\V1\FilterController;
use App\Http\Controllers\Api\V1\FeatureController;
use App\Http\Controllers\Api\V1\RegisterController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\SocialLoginController;
use App\Http\Controllers\Api\V1\DarIntegrationController;

use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\ActivityLogTypeController;
use App\Http\Controllers\Api\V1\ActivityLogUserTypeController;

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

Route::group(['middleware' => ['jwt.verify', 'sanitize.input']], function() {
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
    ];

    foreach ($routes as $route => $controller) {
        Route::get('/' . $route, [$controller . "::class, 'index'"]);
        Route::get('/' . $route . '/{id}', [$controller . "::class, 'show'"])->where('id', '[0-9]+');
        Route::post('/' . $route, [$controller . "::class, 'store'"]);
        Route::patch('/' . $route . '/{id}', [$controller . "::class, '@update'"])->where('id', '[0-9]+');
        Route::delete('/' . $route . '/{id}', [$controller . "::class, '@destroy'"])->where('id', '[0-9]+');
    }
});

// stop all all other routes
Route::any('{path}', function() {
    $response = [
        'message' => 'Resource not found',
    ];

    return response()->json($response, 404);
});