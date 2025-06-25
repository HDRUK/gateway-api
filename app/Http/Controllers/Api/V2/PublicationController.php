<?php

namespace App\Http\Controllers\Api\V2;

use Config;
use Auditor;
use Exception;
use App\Models\Publication;
use Illuminate\Http\Request;
use App\Http\Traits\CheckAccess;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Traits\PublicationsV2Helper;
use App\Http\Traits\RequestTransformation;
use App\Http\Requests\Publication\GetPublication;

class PublicationController extends Controller
{
    use RequestTransformation;
    use CheckAccess;
    use PublicationsV2Helper;

    /**
     * @OA\Get(
     *    path="/api/v2/publications",
     *    operationId="fetch_all_publications_v2",
     *    tags={"Publication"},
     *    summary="PublicationController@indexActive",
     *    description="Get All Publications",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="paper_title",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="string"),
     *       description="Filter tools by paper title"
     *    ),
     *    @OA\Parameter(
     *       name="owner_id",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="int"),
     *       description="Filter tools by owner id"
     *    ),
     *    @OA\Parameter(
     *       name="team_id",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="int"),
     *       description="Filter tools by team id"
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="data",
     *             type="array",
     *             example="[]",
     *             @OA\Items(
     *                type="array",
     *                @OA\Items()
     *             )
     *          ),
     *       ),
     *    ),
     * )
     */
    public function indexActive(Request $request): JsonResponse
    {
        try {
            $input = $request->all();
            $mongoId = $request->query('mongo_id', null);
            $paperTitle = $request->query('paper_title', null);
            $ownerId = $request->query('owner_id', null);
            $teamId = $request->query('team_id', null);
            $filterStatus = $request->query('status', null);
            $perPage = request('per_page', Config::get('constants.per_page'));
            $withRelated = $request->boolean('with_related', true);

            $publications = Publication::where(
                'status',
                '=',
                'ACTIVE'
            )->when($paperTitle, function ($query) use ($paperTitle) {
                return $query->where('paper_title', 'LIKE', '%' . $paperTitle . '%');
            })
            ->when($withRelated, fn ($query) => $query->with(['tools']))
            ->applySorting()
            ->paginate($perPage, ['*'], 'page');

            // Ensure datasets are loaded via the accessor
            if ($withRelated) {
                $publications->getCollection()->transform(function ($publication) {
                    $publication->setAttribute('datasets', $publication->allDatasets);
                    return $publication;
                });
            }

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Publication get all active',
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

    /**
     * @OA\Get(
     *    path="/api/v2/publications/{id}",
     *    operationId="fetch_publications_v2",
     *    tags={"Publication"},
     *    summary="PublicationController@showActive",
     *    description="Get publication by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="publication id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="publication id",
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(
     *             property="data",
     *             type="array",
     *             example="[]",
     *             @OA\Items(
     *                type="array",
     *                @OA\Items()
     *             )
     *          ),
     *       ),
     *    ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found"),
     *          )
     *      )
     * )
     *
     */
    public function showActive(GetPublication $request, int $id): JsonResponse
    {
        try {
            $publication = $this->getPublicationById($id);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Publication get ' . $id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $publication,
            ], Config::get('statuscodes.STATUS_OK.code'));
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
