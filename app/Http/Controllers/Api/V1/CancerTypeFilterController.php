<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\CancerTypeFilter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;

class CancerTypeFilterController extends Controller
{
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

            if ($request->has('parent_id')) {
                $query = CancerTypeFilter::where('parent_id', $request->parent_id);
            } else {
                $query = CancerTypeFilter::whereNull('parent_id');
            }

            if ($request->has('level')) {
                $query->where('level', $request->level);
            }

            $filters = $query->orderBy('sort_order')->get();

            // Load children recursively
            $filters->load('children');

            // Build hierarchical structure
            $result = $this->buildHierarchy($filters);

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

            $filter = CancerTypeFilter::with('children')->where('filter_id', $filter_id)->first();

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
                'data' => $this->formatFilter($filter),
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

    /**
     * Build hierarchical structure from flat collection
     */
    private function buildHierarchy($filters)
    {
        $result = [];
        foreach ($filters as $filter) {
            $result[] = $this->formatFilter($filter);
        }
        return $result;
    }

    /**
     * Format filter with children
     */
    private function formatFilter($filter)
    {
        $formatted = [
            'id' => $filter->id,
            'filter_id' => $filter->filter_id,
            'label' => $filter->label,
            'category' => $filter->category,
            'primary_group' => $filter->primary_group,
            'count' => $filter->count,
            'parent_id' => $filter->parent_id,
            'level' => $filter->level,
            'sort_order' => $filter->sort_order,
        ];

        // Load children if not already loaded
        if (!$filter->relationLoaded('children')) {
            $filter->load('children');
        }

        if ($filter->children->isNotEmpty()) {
            $formatted['children'] = [];
            foreach ($filter->children->sortBy('sort_order') as $child) {
                $formatted['children'][] = $this->formatFilter($child);
            }
        } else {
            $formatted['children'] = [];
        }

        return $formatted;
    }
}
