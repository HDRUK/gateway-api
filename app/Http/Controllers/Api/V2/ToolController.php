<?php

namespace App\Http\Controllers\Api\V2;

use Config;
use Auditor;
use Exception;
use App\Models\Tool;
use App\Models\Collection;
use Illuminate\Http\Request;
use App\Http\Traits\CheckAccess;
use App\Http\Traits\IndexElastic;
use Illuminate\Http\JsonResponse;
use App\Http\Traits\ToolsV2Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V2\Tool\GetTool;
use App\Http\Traits\RequestTransformation;

class ToolController extends Controller
{
    use IndexElastic;
    use RequestTransformation;
    use CheckAccess;
    use ToolsV2Helper;

    /**
     * constructor method
     */
    public function __construct()
    {
        //
    }

    /**
     * @OA\Get(
     *    path="/api/v2/tools",
     *    operationId="fetch_all_tools_v2",
     *    tags={"Tools"},
     *    summary="ToolController@indexActive",
     *    description="Get all tools with optional filters and sorting",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="name",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="string"),
     *       description="Filter tools by name"
     *    ),
     *    @OA\Parameter(
     *       name="sort",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="string", example="name:asc"),
     *       description="Sort tools by a specific field and direction, e.g., 'name:asc' or 'created_at:desc'"
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="data",
     *             type="array",
     *             @OA\Items(type="object")
     *          ),
     *          @OA\Property(property="current_page", type="integer"),
     *          @OA\Property(property="first_page_url", type="string"),
     *          @OA\Property(property="from", type="integer"),
     *          @OA\Property(property="last_page", type="integer"),
     *          @OA\Property(property="last_page_url", type="string"),
     *          @OA\Property(property="links", type="array", @OA\Items(type="object")),
     *          @OA\Property(property="next_page_url", type="string"),
     *          @OA\Property(property="path", type="string"),
     *          @OA\Property(property="per_page", type="integer"),
     *          @OA\Property(property="prev_page_url", type="string"),
     *          @OA\Property(property="to", type="integer"),
     *          @OA\Property(property="total", type="integer"),
     *       ),
     *    ),
     *    @OA\Response(
     *       response="400",
     *       description="Bad request response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string")
     *       )
     *    ),
     *    @OA\Response(
     *       response="500",
     *       description="Internal server error",
     *       @OA\JsonContent(
     *          @OA\Property(property="error", type="string")
     *       )
     *    )
     * )
     */
    public function indexActive(Request $request): JsonResponse
    {
        try {
            $filterName = $request->query('name', null);
            $perPage = request('per_page', Config::get('constants.per_page'));

            // Perform query for the matching tools with filters, sorting, and pagination
            $tools = Tool::with([
                'user',
                'tag',
                'team',
                'license',
                'publications',
                'durs',
                'collections',
                'category',
                'typeCategory',
            ])
            ->where(
                'status',
                '=',
                'ACTIVE'
            )
            ->when($filterName, function ($query) use ($filterName) {
                return $query->where('name', 'like', '%' . $filterName . '%');
            })
            ->where('enabled', 1)
            ->applySorting()
            ->paginate($perPage, ['*'], 'page');

            // Transform collection to include datasets
            $tools->map(function ($tool) {
                $tool->setAttribute('datasets', $tool->allDatasets  ?? []);
                return $tool;
            });

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Tool get all active',
            ]);

            return response()->json(
                $tools
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
     *    path="/api/v2/tools/{id}",
     *    operationId="fetch_tools_v2",
     *    tags={"Tools"},
     *    summary="ToolController@showActive",
     *    description="Get tool by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="tool id",
     *       required=true,
     *       example="1",
     *       @OA\Schema( type="integer", description="tool id" ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property( property="message", type="string", example="success" ),
     *          @OA\Property( property="data", type="array", example="[]", @OA\Items( type="array", @OA\Items() ) ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="unauthorized")
     *       )
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="not found"),
     *       )
     *    )
     * )
     */
    public function showActive(GetTool $request, int $id): JsonResponse
    {
        try {
            $tool = $this->getToolById($id, onlyActive: true, onlyActiveRelated: true);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Tool get ' . $id,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $tool,
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
