<?php

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RegisterController;
use App\Http\Controllers\Api\V1\LoginGoogleController;
use App\Http\Controllers\Api\V1\LoginLinkedinController;

Route::get('/test', function() {
    return Response::json([
        'message' => 'lorem ipsum dolor sit amet, consectetur adip',
    ]);
});

Route::post('/register', [RegisterController::class, 'create']);
Route::post('/auth', [AuthController::class, 'checkAuthorization']);

// login with google credentials
Route::get('/auth/google', [LoginGoogleController::class, 'google']);
Route::get('/auth/google/redirect', [LoginGoogleController::class, 'googleRedirect']);

// login with linkedin credentials
Route::get('/auth/linkedin', [LoginLinkedinController::class, 'linkedin']);
Route::get('/auth/linkedin/redirect', [LoginLinkedinController::class, 'linkedinRedirect']);

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