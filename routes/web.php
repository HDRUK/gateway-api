<?php

use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SSO\JwksController;
use App\Http\Middleware\AppendTokenResponse;
use App\Http\Controllers\SSO\OpenIdController;
use App\Http\Controllers\SSO\CustomAuthorizationController;
use Laravel\Passport\Http\Controllers\AccessTokenController;

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

Route::get('/oauth/authorize', [CustomAuthorizationController::class, 'customAuthorize']);
// Route::post('/oauth/token', [AccessTokenController::class, 'issueToken'])->middleware(AppendTokenResponse::class);
Route::post('/oauth/token', [AccessTokenController::class, 'issueToken']);

Route::get('/oauth/.well-known/jwks', [JwksController::class, 'getJwks']);

// strange call from rquest dev: sometimes call one and sometimes another .... no idea
Route::get('/oauth/.well-known/openid-configuration', [OpenIdController::class, 'getOpenIdConfiguration']);
Route::get('/.well-known/openid-configuration', [OpenIdController::class, 'getOpenIdConfiguration']);

Route::middleware('auth:api')->get('/oauth/userinfo', function (Request $request) {
    \Log::info('/oauth/userinfo :: ' . json_encode($request));
    return $request->user();
});

Route::middleware('auth:api')->get('/oauth/logmeout', function (Request $request) {
    $user = $request->user();
    $accessToken = $user->token();

    DB::table('oauth_refresh_tokens')->where('access_token_id', $accessToken->id)->delete();
    $accessToken->delete();

    return response()->json([
        'message' => 'Revoked',
    ]);
});

// stop all all other routes
Route::any('{path}', function () {
    $response = [
        'message' => 'Resource not found',
    ];

    return response()->json($response)
        ->setStatusCode(404);
});
