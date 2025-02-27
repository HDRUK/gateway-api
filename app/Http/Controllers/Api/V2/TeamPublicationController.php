<?php

namespace App\Http\Controllers\Api\V2;

use Config;
use Auditor;
use Exception;
use App\Models\Publication;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V2\Publication\GetPublicationByTeamAndStatus;

class TeamPublicationController extends Controller
{
    public function __construct()
    {
        //
    }

    /**
      * @OA\Get(
      *     path="/api/v2/teams/{teamId}/publications/{status}",
      *     operationId="fetch_all_publications_by_team_and_status_v2",
      *     tags={"Publication"},
      *     summary="TeamPublicationController@indexStatus",
      *     description="Returns a list of a teams publications",
      *     @OA\Parameter(
      *         name="teamId",
      *         in="path",
      *         description="ID of the team",
      *         required=true,
      *         @OA\Schema(
      *             type="integer",
      *             format="int64"
      *         )
      *     ),
      *     @OA\Parameter(
      *         name="status",
      *         in="path",
      *         description="Status of the team (active, draft, or archived). Defaults to active if not provided.",
      *         required=false,
      *         @OA\Schema(
      *             type="string",
      *             enum={"active", "draft", "archived"},
      *             default="active"
      *         )
      *     ),
      *     @OA\Response(
      *        response="200",
      *        description="Success response",
      *        @OA\JsonContent(
      *           @OA\Property(
      *              property="data",
      *              type="array",
      *              example="[]",
      *              @OA\Items(
      *                 type="array",
      *                 @OA\Items()
      *              )
      *           ),
      *        ),
      *     ),
      *     @OA\Response(
      *         response=404,
      *         description="Not Found"
      *     )
      * )
      *
      * @param  GetPublicationByTeamAndStatus  $request
      * @param  int  $teamId
      * @param  string|null  $status
      * @return JsonResponse
      */
    public function indexStatus(GetPublicationByTeamAndStatus $request, int $teamId, ?string $status = 'active'): JsonResponse
    {
        try {
            $perPage = request('per_page', Config::get('constants.per_page'));

            $publications = Publication::where([
                    'team_id' => $teamId,
                    'status' => strtoupper($status),
                ])
                ->with(['tools']);

            if ($request->has('sort')) {
                $publications = $publications->applySorting();
            }

            $publications = $publications->paginate($perPage, ['*'], 'page');

            $publications->getCollection()->transform(function ($publication) {
                $publication->setAttribute('datasets', $publication->allDatasets);
                return $publication;
            });

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Publication get all',
            ]);

            return response()->json(
                $publications
            );
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }
}
