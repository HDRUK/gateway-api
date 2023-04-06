<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TestController;
use App\Http\Controllers\Api\V1\RegisterController;
use App\Http\Controllers\Api\V1\SocialLoginController;

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
});

// stop all all other routes
Route::any('{path}', function() {
    $response = [
        'message' => 'Resource not found',
    ];

    return response()->json($response, 404);
});