<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SSO\CustomUserController;
use App\Http\Controllers\SSO\CustomLogoutController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('/status', function (Request $request) {
    return response()->json(['message' => 'OK'])
        ->setStatusCode(200);
});

Route::get('/email', function (Request $reqest) {
    Artisan::call('app:email_service', [
        '--identifier' => 'example_template',
        '--recipient' => 'laminatefish@gmail.com',
        '--replace_map' => [
            '[[header_text]]' => 'Something here 1',
            '[[button_text]]' => 'Click me!',
            '[[subheading_text]]' => 'Something here 2',
        ],
    ]);
});

# bcplatform
Route::get('/oauth/userinfo', [CustomUserController::class, 'userInfo'])->middleware('auth:api');
Route::match(['get', 'post'], '/oauth/logmeout', [CustomLogoutController::class, 'rquestLogout']);
Route::match(['get', 'post'], '/oauth2/logout', [CustomLogoutController::class, 'rquestLogout']);

// stop all all other routes
Route::any('{path}', function () {
    $response = [
        'message' => 'Resource not found',
    ];

    return response()->json($response)
        ->setStatusCode(404);
});
