<?php

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;
use MetadataManagementController AS MMC;
use App\Http\Controllers\FilterController;

use App\Http\Controllers\ServiceLayerController;
use App\Http\Controllers\Api\V1\DatasetController;
use App\Http\Controllers\SSO\CustomAuthorizationController;


#Route::get('/federations', [ServiceLayerController::class, 'getActiveFederationApplications']);
#Route::patch('/federations/{id}', [ServiceLayerController::class, 'setFederationInvalidRunState']);
#Route::post('/federations', [DatasetController::class, 'store']);
#Route::put('/federations/update/{pid}', [DatasetController::class, 'updateByPid']);
#Route::delete('/federations/delete/{pid}', [DatasetController::class, 'destroyByPid']);
#Route::get('/datasets', [ServiceLayerController::class, 'getDatasets']);
#Route::get('/datasets/{pid}', [ServiceLayerController::class, 'getDatasetFromPid']);
#Route::post('/audit', [ServiceLayerController::class, 'audit']);

Route::get('/traser', function(Request $request) {
    MMC::validateDataModelType(
        json_encode($request->all()), 
        Config::get('metadata.GWDM.name'),
        Config::get('metadata.GWDM.version')
    );
});

foreach (config("service_routes") as $service => $paths) {
    foreach ($paths as $path => $methods) {
        foreach ($methods as $method => $middlewares) {
            Route::{$method}($service.$path,  [ServiceLayerController::class, $service] )
                ->where('any', '.*')
                ->middleware($middlewares);
        }
    }
}
