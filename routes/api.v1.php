<?php

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;
use App\Http\Controllers\Api\V1\AuthController;
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

Route::group(['middleware' => 'jwt.verify'], function() {
    Route::get('/test', [TestController::class, 'test']);
});

// stop all all other routes
Route::any('{path}', function() {
    $response = [
        'message' => 'Resource not found',
    ];

    return response()->json($response, 404);
});