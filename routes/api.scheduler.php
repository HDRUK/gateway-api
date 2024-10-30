<?php

use App\Jobs\AliasReplyScannerJob;
use Illuminate\Support\Facades\Route;

Route::get('/cohort_user_expiry', function (Request $reqest) {
    Artisan::call('app:cohort-user-expiry');
});

Route::get('/alias_reply_scanner', function (Request $reqest) {
    // Artisan::call('app:alias-reply-scanner');
    AliasReplyScannerJob::dispatch();
});

Route::get('/update_licenses', function (Request $reqest) {
    Artisan::call('app:update-licenses');
});

Route::get('/sync_hubspot_contacts', function (Request $reqest) {
    Artisan::call('app:sync-hubspot-contacts');
});

Route::any('{path}', function () {
    $response = [
        'message' => 'Resource not found',
    ];

    return response()->json($response, 404);
});
