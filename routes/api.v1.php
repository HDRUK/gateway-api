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

    // tags routes
    Route::get('/tags', [TagController::class, 'index']);
    Route::get('/tags/{id}', [TagController::class, 'show'])->where('id', '[0-9]+');
    Route::post('/tags', [TagController::class, 'store']);
    Route::patch('/tags/{id}', [TagController::class, 'update'])->where('id', '[0-9]+');
    Route::delete('/tags/{id}', [TagController::class, 'destroy'])->where('id', '[0-9]+');

    // features routes
    Route::get('/features', [FeatureController::class, 'index']);
    Route::get('/features/{id}', [FeatureController::class, 'show'])->where('id', '[0-9]+');
    Route::post('/features', [FeatureController::class, 'store']);
    Route::patch('/features/{id}', [FeatureController::class, 'update'])->where('id', '[0-9]+');
    Route::delete('/features/{id}', [FeatureController::class, 'destroy'])->where('id', '[0-9]+');

    // Filter routes
    Route::get('/filters', [FilterController::class, 'index']);
    Route::get('/filters/{id}', [FilterController::class, 'show'])->where('id', '[0-9]+');
    Route::post('/filters', [FilterController::class, 'store']);
    Route::patch('/filters/{id}', [FilterController::class, 'update'])->where('id', '[0-9]+');
    Route::delete('/filters/{id}', [FilterController::class, 'destroy'])->where('id', '[0-9]+');

    // DarIntegration routes
    Route::get('/dar-integrations', [DarIntegrationController::class, 'index']);
    Route::get('/dar-integrations/{id}', [DarIntegrationController::class, 'show'])->where('id', '[0-9]+');
    Route::post('/dar-integrations', [DarIntegrationController::class, 'store']);
    Route::patch('/dar-integrations/{id}', [DarIntegrationController::class, 'update'])->where('id', '[0-9]+');
    Route::delete('/dar-integrations/{id}', [DarIntegrationController::class, 'destroy'])->where('id', '[0-9]+');

    // Team routes
    Route::get('/teams', [TeamController::class, 'index']);
    Route::get('/teams/{id}', [TeamController::class, 'show']);
    Route::post('/teams', [TeamController::class, 'store']);
    Route::patch('/teams/{id}', [TeamController::class, 'update']);
    Route::delete('/teams/{id}', [TeamController::class, 'destroy']);

    // Tools routes
    Route::get('/tools', [ToolController::class, 'index']);
    Route::get('/tools/{id}', [ToolController::class, 'show'])->where('id', '[0-9]+');
    Route::post('/tools', [ToolController::class, 'store']);
    Route::patch('/tools/{id}', [ToolController::class, 'update'])->where('id', '[0-9]+');
    Route::delete('/tools/{id}', [ToolController::class, 'destroy'])->where('id', '[0-9]+');

    // ActivityLog routes
    Route::get('/activity_logs', [ActivityLogController::class, 'index']);
    Route::get('/activity_logs/{id}', [ActivityLogController::class, 'show'])->where('id', '[0-9]+');
    Route::post('/activity_logs', [ActivityLogController::class, 'store']);
    Route::patch('/activity_logs/{id}', [ActivityLogController::class, 'update'])->where('id', '[0-9]+');
    Route::delete('/activity_logs/{id}', [ActivityLogController::class, 'destroy'])->where('id', '[0-9]+');

    // ActivityLogType routes
    Route::get('/activity_log_types', [ActivityLogTypeController::class, 'index']);
    Route::get('/activity_log_types/{id}', [ActivityLogTypeController::class, 'show'])->where('id', '[0-9]+');
    Route::post('/activity_log_types', [ActivityLogTypeController::class, 'store']);
    Route::patch('/activity_log_types/{id}', [ActivityLogTypeController::class, 'update'])->where('id', '[0-9]+');
    Route::delete('/activity_log_types/{id}', [ActivityLogTypeController::class, 'destroy'])->where('id', '[0-9]+');

    // AcitivityLogUserType routes
    Route::get('/activity_log_user_types', [ActivityLogUserTypeController::class, 'index']);
    Route::get('/activity_log_user_types/{id}', [ActivityLogUserTypeController::class, 'show'])->where('id', '[0-9]+');
    Route::post('/activity_log_user_types', [ActivityLogUserTypeController::class, 'store']);
    Route::patch('/activity_log_user_types/{id}', [ActivityLogUserTypeController::class, 'update'])->where('id', '[0-9]+');
    Route::delete('/activity_log_user_types/{id}', [ActivityLogUserTypeController::class, 'destroy'])->where('id', '[0-9]+');
});

// stop all all other routes
Route::any('{path}', function() {
    $response = [
        'message' => 'Resource not found',
    ];

    return response()->json($response, 404);
});