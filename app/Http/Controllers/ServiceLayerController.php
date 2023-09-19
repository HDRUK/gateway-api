<?php

namespace App\Http\Controllers;

use App\Models\Federation;

use Illuminate\Http\Request;

class ServiceLayerController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/services/federations",
     *     operationId="getActiveFederationApplications",
     *     summary="ServiceLayerController@getActiveFederationApplications",
     *     description="Get a list of all actve federations for use in service layer",
     *     @OA\Response(
     *         response="200",
     *         description="Success response",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 type="array",
     *                 example="[]",
     *                 @OA\Items(
     *                     type="array",
     *                     @OA\Items()
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getActiveFederationApplications(Request $request)
    {
        $federations = Federation::with('team')->where('enabled', true)->get();
        return response()->json($federations);
    }
}
