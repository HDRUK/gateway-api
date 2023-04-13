<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TestController;
use App\Http\Controllers\Api\V1\RegisterController;
use App\Http\Controllers\Api\V1\SocialLoginController;
use App\Http\Controllers\Api\V1\FilterController;
use App\Http\Controllers\Api\V1\DarIntegrationController;
use App\Http\Controllers\Api\V1\PublisherController;

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

Route::group(['middleware' => 'jwt.verify'], function() {
    Route::any('/test', [TestController::class, 'test']);

    // tags
    Route::get('/tags/{id?}', [TagController::class, 'show'])->where('id', '[0-9]+');
    Route::post('/tags', [TagController::class, 'store']);
    Route::patch('/tags/{id}', [TagController::class, 'update'])->where('id', '[0-9]+');
    Route::delete('/tags/{id}', [TagController::class, 'destroy'])->where('id', '[0-9]+');
    Route::patch('/tags/{id}/restore', [TagController::class, 'restore'])->where('id', '[0-9]+');

    // Filter routes
    Route::get('/filters', [FilterController::class, 'index']);
    Route::get('/filters/{id}', [FilterController::class, 'show']);
    Route::post('/filters', [FilterController::class, 'store']);
    Route::patch('/filters/{id}', [FilterController::class, 'update']);
    Route::delete('/filters/{id}', [FilterController::class, 'destroy']);

    // DarIntegration routes
    Route::get('/dar-integrations', [DarIntegrationController::class, 'index']);
    Route::get('/dar-integrations/{id}', [DarIntegrationController::class, 'show']);
    Route::post('/dar-integrations', [DarIntegrationController::class, 'store']);
    Route::patch('/dar-integrations/{id}', [DarIntegrationController::class, 'update']);
    Route::delete('/dar-integrations/{id}', [DarIntegrationController::class, 'destroy']);

    // Publisher routes
    Route::get('/publishers', [PublisherController::class, 'index']);
    Route::get('/publishers/{id}', [PublisherController::class, 'show']);
    Route::post('/publishers', [PublisherController::class, 'store']);
    Route::patch('/publishers/{id}', [PublisherController::class, 'update']);
    Route::delete('/publishers/{id}', [PublisherController::class, 'destroy']);
});

// stop all all other routes
Route::any('{path}', function() {
    $response = [
        'message' => 'Resource not found',
    ];

    return response()->json($response, 404);
});