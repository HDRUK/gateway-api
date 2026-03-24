<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\CancerTypeFilterService;
use App\Http\Resources\CancerTypeFilterResource;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;

class CancerTypeFilterController extends Controller
{
    public function __construct(
        private readonly CancerTypeFilterService $cancerTypeFilterService
    ) {
    }

    /**
     * @OA\Get(
     *    path="/api/v1/cancer-type-filters",
     *    operationId="getCancerTypeFilters",
     *    tags={"CancerTypeFilter"},
     *    summary="Get all cancer type filters",
     *    description="Returns a hierarchical tree of cancer type filters",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="parent_id",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="integer"),
     *       description="Filter by parent ID"
     *    ),
     *    @OA\Parameter(
     *       name="level",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="integer"),
     *       description="Filter by hierarchy level"
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="data",
     *             type="array",
     *             @OA\Items(
     *                @OA\Property(property="id", type="integer", example=1),
     *                @OA\Property(property="filter_id", type="string", example="0_0"),
     *                @OA\Property(property="label", type="string", example="cancerTypes"),
     *                @OA\Property(property="category", type="string", example="filters"),
     *                @OA\Property(property="primary_group", type="string", example="cancer-type"),
     *                @OA\Property(property="count", type="string", example="0"),
     *                @OA\Property(property="parent_id", type="integer", nullable=true),
     *                @OA\Property(property="level", type="integer", example=0),
     *                @OA\Property(property="children", type="array", @OA\Items(type="object"))
     *             )
     *          )
     *       )
     *    )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $parentId = $request->has('parent_id') ? (int) $request->parent_id : null;
            $level = $request->has('level') ? (int) $request->level : null;
            $result = $this->cancerTypeFilterService->list($parentId, $level);
            $result = array_map(
                fn ($item) => CancerTypeFilterResource::make($item)->resolve($request),
                $result
            );

            Auditor::log([
                'user_id' => (int)($jwtUser['id'] ?? 0),
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Cancer type filters retrieved',
            ]);

            return response()->json([
                'data' => $result,
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)($jwtUser['id'] ?? 0),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'INTERNAL_SERVER_ERROR',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/cancer-type-filters/{filter_id}",
     *    operationId="getCancerTypeFilter",
     *    tags={"CancerTypeFilter"},
     *    summary="Get a single cancer type filter",
     *    description="Returns a single cancer type filter with its children by filter_id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="filter_id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="string"),
     *       description="Filter ID (e.g., 0_0, 0_0_2_59)",
     *       example="0_0_2_59"
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="data",
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="filter_id", type="string", example="0_0"),
     *             @OA\Property(property="label", type="string", example="cancerTypes"),
     *             @OA\Property(property="category", type="string", example="filters"),
     *             @OA\Property(property="primary_group", type="string", example="cancer-type"),
     *             @OA\Property(property="count", type="string", example="0"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true),
     *             @OA\Property(property="level", type="integer", example=0),
     *             @OA\Property(property="children", type="array", @OA\Items(type="object"))
     *          )
     *       )
     *    ),
     *    @OA\Response(
     *       response="404",
     *       description="Not found",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="string", example="NOT_FOUND"),
     *          @OA\Property(property="message", type="string", example="Cancer type filter not found")
     *       )
     *    )
     * )
     */
    public function show(string $filter_id): JsonResponse
    {
        try {
            $input = request()->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $filter = $this->cancerTypeFilterService->findByFilterId($filter_id);
            if (!$filter) {
                throw new NotFoundException('Cancer type filter not found');
            }

            Auditor::log([
                'user_id' => (int)($jwtUser['id'] ?? 0),
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Cancer type filter ' . $filter_id . ' retrieved',
            ]);

            return response()->json([
                'data' => CancerTypeFilterResource::make($filter)->resolve($request),
            ], 200);
        } catch (NotFoundException $e) {
            return response()->json([
                'status' => 'NOT_FOUND',
                'message' => $e->getMessage(),
            ], 404);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)($jwtUser['id'] ?? 0),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'INTERNAL_SERVER_ERROR',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
