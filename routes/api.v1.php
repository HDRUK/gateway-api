<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\TestController;

Route::get('/test', function() {
    return Response::json([
        'message' => 'lorem ipsum dolor sit amet, consectetur adip',
    ]);
});

// Route::group(['namespace' => 'App\Http\Controllers\Api\V1', 'middleware' => ['jwt.verify', 'sanitize.input']], function() {
//     // Route::any('/test', [TestController::class, 'test']);
//     // Route::any('/test/check_access', [TestController::class, 'testCheckRoles'])->middleware(['check.access:roles,reviewer|custodian.team.admin']);
//     // Route::any('/test/check_access', [TestController::class, 'testCheckAccess'])->middleware(['check.access:permissions,datasets.read|dur.read|filters.read']);
// });

foreach (config('routes') as $route) {
    Route::{$route['method']}($route['path'], $route['namespaceController'] . '\\' . $route['methodController'])
        ->where($route['constraint'])
        ->middleware($route['middleware']);
}

// stop all all other routes
Route::any('{path}', function() {
    $response = [
        'message' => 'Resource not found',
    ];

    return response()->json($response, 404);
});