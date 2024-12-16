<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ServiceLayerController;
use App\Http\Controllers\Api\V1\DatasetController;

Route::get('/federations', [ServiceLayerController::class, 'getActiveFederationApplications']);
Route::patch('/federations/{id}', [ServiceLayerController::class, 'setFederationInvalidRunState'])->middleware('jwt.verify');
Route::post('/federations', [DatasetController::class, 'store'])->middleware('jwt.verify');
Route::put('/federations/update/{pid}', [DatasetController::class, 'updateByPid']);
Route::delete('/federations/delete/{pid}', [DatasetController::class, 'destroyByPid'])->middleware('jwt.verify');
Route::get('/datasets', [ServiceLayerController::class, 'getDatasets']);
Route::get('/datasets/{pid}', [ServiceLayerController::class, 'getDatasetFromPid']);
Route::post('/audit', [ServiceLayerController::class, 'audit']);

Route::any('/traser', [ServiceLayerController::class, 'traser']);
