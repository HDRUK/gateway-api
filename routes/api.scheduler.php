<?php

use App\Jobs\AliasReplyScannerJob;
use Illuminate\Support\Facades\Route;

Route::get('/cohort_user_expiry', function (Request $reqest) {
    Artisan::call('app:cohort-user-expiry');

    return response()->json([
        'message' => 'ok',
    ], 200);
});

Route::get('/alias_reply_scanner', function (Request $reqest) {
    // Artisan::call('app:alias-reply-scanner');
    AliasReplyScannerJob::dispatch();

    return response()->json([
        'message' => 'ok',
    ], 200);
});

Route::get('/update_licenses', function (Request $reqest) {
    Artisan::call('app:update-licenses');

    return response()->json([
        'message' => 'ok',
    ], 200);
});

Route::get('/sync_hubspot_contacts', function (Request $reqest) {
    Artisan::call('app:sync-hubspot-contacts');

    return response()->json([
        'message' => 'ok',
    ], 200);
});

Route::get('/gateway_metadata_ingestion', function (Request $request) {
    Artisan::call('app:gateway-metadata-ingestion');

    return response()->json([
        'message' => 'ok',
    ], 200);
});

Route::any('{path}', function () {
    $response = [
        'message' => 'Resource not found',
    ];

    return response()->json($response, 404);
});
