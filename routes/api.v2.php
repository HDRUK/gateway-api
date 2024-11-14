<?php

use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return Response::json([
        'message' => 'lorem ipsum dolor sit amet, consectetur adip',
    ]);
});

foreach (config('routes_v2') as $route) {
    Route::{$route['method']}($route['path'], $route['namespaceController'] . '\\' . $route['methodController'])
        ->where($route['constraint'])
        ->middleware($route['middleware']);
}

// stop all all other routes
Route::any('{path}', function () {
    $response = [
        'message' => 'Resource not found',
    ];

    return response()->json($response, 404);
});
