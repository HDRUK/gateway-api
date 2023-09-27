<?php

namespace App\Http\Controllers;

use Config;
use Exception;
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
        $federations = Federation::with('team')
            ->where('enabled', 1)
            ->where('tested', 1)
            ->get();
        return response()->json($federations);
    }

    /**
     * @OA\Patch(
     *     path="/api/services/federations/{id}",
     *     operationId="setFederationInvalidRunState",
     *     summary="ServiceLayerController@setFederationInvalidRunState",
     *     description="Sets the specified federations run state to invalid, when errors are found",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="federation id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *             type="integer",
     *             description="federation id",
     *         ),
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enabled and tested flags to set",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="enabled", type="integer", example="0"),
     *                 @OA\Property(property="tested", type="integer", example="0"),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="success"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="not found"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="error"),
     *         ),
     *     ),
     * )
     */
    public function setFederationInvalidRunState(Request $request, int $id)
    {
        try {
            $federation = Federation::where('id', $id)->first();
            $federation->enabled = 0;
            $federation->tested = 0;
            if ($federation->save()) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                ]);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
