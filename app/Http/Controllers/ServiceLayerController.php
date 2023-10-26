<?php

namespace App\Http\Controllers;

use Auditor;
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

    /**
     * @OA\Post(
     *     path="/api/services/audit",
     *     operationId="audit",
     *     summary="ServiceLayerController@audit",
     *     description="",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload to audit against the system",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="user_id", type="integer", example="0"),
     *                 @OA\Property(property="team_id", type="integer", example="0"),
     *                 @OA\Property(property="action_type", type="string", example="Action Type"),
     *                 @OA\Property(property="action_service", type="string", example="Action Service"),
     *                 @OA\Property(property="description", type="string", example="Description"),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful Response",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="success"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error Response",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="error"),
     *         ),
     *     ),
     * )
     */
    public function audit(Request $request)
    {
        $input = $request->all();

        try {
            $retVal = Auditor::log(
                $input['user_id'],
                $input['team_id'],
                $input['action_type'],
                $input['action_service'],
                $input['description']
            );

            if ($retVal) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                ]);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
