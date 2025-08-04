<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SSO\JwksController;
use App\Http\Middleware\AppendTokenResponse;
use App\Http\Middleware\AppendJWTTokenResponse;
use App\Http\Controllers\SSO\OAuth2Controller;
use App\Http\Controllers\SSO\OpenIdController;
use App\Http\Controllers\SSO\CustomAuthorizationController;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use App\Http\Controllers\SSO\CustomAccessTokenController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/oauth/authorize', [CustomAuthorizationController::class, 'customAuthorize'])->middleware('web');
Route::post('/oauth/token', [AccessTokenController::class, 'issueToken'])->middleware(AppendTokenResponse::class);

Route::get('/oauth2/authorize', [OAuth2Controller::class, 'customAuthorize'])->middleware('web');
Route::post('/oauth2/token', [CustomAccessTokenController::class, 'issueOAuthToken'])->middleware(AppendJWTTokenResponse::class);

Route::get('/oauth/.well-known/jwks', [JwksController::class, 'getJwks']);

// strange call from rquest dev: sometimes call one and sometimes another .... no idea
Route::get('/oauth/.well-known/openid-configuration', [OpenIdController::class, 'getOpenIdConfiguration']);
Route::get('/.well-known/openid-configuration', [OpenIdController::class, 'getOpenIdConfiguration']);

// stop all all other routes
Route::any('{path}', function () {
    $response = [
        'message' => 'Resource not found',
    ];

    return response()->json($response)
        ->setStatusCode(404);
});
