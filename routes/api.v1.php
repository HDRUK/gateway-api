<?php

use App\Jobs\SendEmailJob;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TestController;
use App\Http\Controllers\Api\V1\DatasetController;
use App\Http\Controllers\Api\V1\RegisterController;
use App\Http\Controllers\Api\V1\TeamUserController;
use App\Http\Controllers\Api\V1\SocialLoginController;

use App\Http\Controllers\Api\V1\EmailTemplateController;

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

Route::group(['namespace' => 'App\Http\Controllers\Api\V1', 'middleware' => ['jwt.verify', 'sanitize.input']], function() {
    Route::any('/test', [TestController::class, 'test']);

    $routes = [
        'tags' => 'TagController',
        'features' => 'FeatureController',
        'filters' => 'FilterController',
        'dar-integrations' => 'DarIntegrationController',
        'teams' => 'TeamController',
        'tools' => 'ToolController',
        'activity_logs' => 'ActivityLogController',
        'activity_log_types' => 'ActivityLogTypeController',
        'activity_log_user_types' => 'ActivityLogUserTypeController',
        'permissions' => 'PermissionController',
        'users' => 'UserController',
        'notifications' => 'NotificationController',
        'reviews' => 'ReviewController',
        'sectors' => 'SectorController',
        'collections' => 'CollectionController',
        'audit_logs' => 'AuditLogController',
        'data_use_registers' => 'DataUseRegisterController',
        'applications' => 'ApplicationController',
        'roles' => 'RoleController',
        // 'emailtemplates' => 'EmailTemplateController',
    ];

    foreach ($routes as $path => $controller) {
        Route::get('/' . $path, ['as' => $path . '.get.index', 'uses' => $controller . '@index']);
        Route::get('/' . $path . '/{id}', ['as' => $path . '.get.show', 'uses' => $controller . '@show'])->where('id', '[0-9]+');
        Route::post('/' . $path, ['as' => $path . '.post.store', 'uses' => $controller . '@store']);
        Route::put('/' . $path . '/{id}', ['as' => $path . '.put.update', 'uses' => $controller . '@update'])->where('id', '[0-9]+');
        Route::patch('/' . $path . '/{id}', ['as' => $path . '.patch.update', 'uses' => $controller . '@edit'])->where('id', '[0-9]+');
        Route::delete('/' . $path . '/{id}', ['as' => $path . '.delete.destroy', 'uses' => $controller . '@destroy'])->where('id', '[0-9]+');
    }

    Route::post('/teams/{teamId}/users', [TeamUserController::class, 'store'])->where('teamId', '[0-9]+');
    Route::put('/teams/{teamId}/users/{userId}', [TeamUserController::class, 'update'])->where(['teamId' => '[0-9]+', 'userId' => '[0-9]+']);
    Route::delete('/teams/{teamId}/users/{userId}', [TeamUserController::class, 'destroy'])->where(['teamId' => '[0-9]+', 'userId' => '[0-9]+']);
    
    Route::post('/dispatch_email', 'EmailController@dispatchEmail');
    Route::post('/logout', 'LogoutController@logout');

    Route::get('/datasets', [DatasetController::class, 'index']);
    Route::get('/datasets/{id}', [DatasetController::class, 'show'])->where('id', '[0-9]+');
    Route::post('/datasets', [DatasetController::class, 'store']);
    Route::delete('/datasets/{id}', [DatasetController::class, 'destroy'])->where('id', '[0-9]+');
    
});

// routes
Route::get('/emailtemplates', [EmailTemplateController::class, 'index']);
Route::get('/emailtemplates/{id}', [EmailTemplateController::class, 'show'])->where('id', '[0-9]+');
Route::post('/emailtemplates', [EmailTemplateController::class, 'store']);
Route::put('/emailtemplates/{id}', [EmailTemplateController::class, 'update'])->where('id', '[0-9]+');
Route::patch('/emailtemplates/{id}', [EmailTemplateController::class, 'edit'])->where('id', '[0-9]+');
Route::delete('/emailtemplates/{id}', [EmailTemplateController::class, 'destroy'])->where('id', '[0-9]+');

Route::get('email-test', function () {
    $to = [
        'to' => [
            'email' => 'loki.sinclair@hdruk.ac.uk',
            'name' => 'Loki Sinclair',
        ],
    ];

    $template = EmailTemplate::where('identifier', '=', 'example_template')->first();

    $replacements = [
        '[[header_text]]' => 'Health Data Research UK',
        '[[button_text]]' => 'Click me!',
        '[[subheading_text]]' => 'Sub Heading Something or other',
    ];

    SendEmailJob::dispatch($to, $template, $replacements);
});

// stop all all other routes
Route::any('{path}', function() {
    $response = [
        'message' => 'Resource not found',
    ];

    return response()->json($response, 404);
});