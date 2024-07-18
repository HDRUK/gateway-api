<?php

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;
use MetadataManagementController AS MMC;
use App\Http\Controllers\FilterController;

use App\Http\Controllers\ServiceLayerController;
use App\Http\Controllers\Api\V1\DatasetController;
use App\Http\Controllers\SSO\CustomAuthorizationController;

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

Route::get('/services/federations', [ServiceLayerController::class, 'getActiveFederationApplications']);
Route::patch('/services/federations/{id}', [ServiceLayerController::class, 'setFederationInvalidRunState']);
Route::post('/services/federations', [DatasetController::class, 'store']);
Route::put('/services/federations/update/{pid}', [DatasetController::class, 'updateByPid']);
Route::delete('/services/federations/delete/{pid}', [DatasetController::class, 'destroyByPid']);
Route::get('/services/datasets', [ServiceLayerController::class, 'getDatasets']);
Route::get('/services/datasets/{pid}', [ServiceLayerController::class, 'getDatasetFromPid']);
Route::post('/services/audit', [ServiceLayerController::class, 'audit']);

Route::get('/services/traser', function(Request $request) {
    MMC::validateDataModelType(
        json_encode($request->all()), 
        Config::get('metadata.GWDM.name'),
        Config::get('metadata.GWDM.version')
    );
});


Route::any('/services/quba{any}', 
    [ServiceLayerController::class, 
    'quba', 
] )->where('any', '.*');


Route::any('/services/daras{any}', 
    [ServiceLayerController::class, 
    'daras', 
] )->where('any', '.*');

# bcplatform
Route::get('/sso/authorize', [CustomAuthorizationController::class, 'customAuthorize']);



// stop all all other routes
Route::any('{path}', function() {
    $response = [
        'message' => 'Resource not found',
    ];

    return response()->json($response)
        ->setStatusCode(404);
});