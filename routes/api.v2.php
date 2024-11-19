<?php

use Illuminate\Support\Facades\Route;

foreach (config('routes_v2') as $route) {
    Route::{$route['method']}($route['path'], $route['namespaceController'] . '\\' . $route['methodController'])
        ->where($route['constraint'])
        ->middleware($route['middleware']);
}

// stop all other routes
Route::any('{path}', function () {
    $response = [
        'message' => 'Resource not found',
    ];

    return response()->json($response, 404);
});
