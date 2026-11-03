<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Exception;
use App\Models\CancerTypeFilter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Context\PartnerContext;
use App\Services\CancerTypeFilterService;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Exceptions\BadRequestException;
use App\Http\Traits\Responses;
use App\Http\Requests\CancerTypeFilter\EditCancerTypeFilter;
use App\Http\Requests\CancerTypeFilter\CreateCancerTypeFilter;
use App\Http\Requests\CancerTypeFilter\UpdateCancerTypeFilter;

class CancerTypeFilterController extends Controller
{
    use Responses;

    public function __construct(
        private readonly CancerTypeFilterService $cancerTypeFilterService,
        private readonly PartnerContext $partnerContext
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
     *                @OA\Property(property="description", type="string", nullable=true, example=""),
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
        $input = $request->all();
        $jwtUser = $request->jwtUser();

        try {
            $parentId = $request->has('parent_id') ? (int) $request->parent_id : null;
            $level = $request->has('level') ? (int) $request->level : null;
            $result = $this->cancerTypeFilterService->list($parentId, $level);
            $resourceClass = $this->partnerContext->indexResourceFor(CancerTypeFilter::class);
            $result = array_map(
                fn ($item) => $resourceClass::make($item)->resolve($request),
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

            return $this->errorResponse($e->getMessage());
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
     *             @OA\Property(property="description", type="string", nullable=true, example=""),
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
    public function show(Request $request, string $filter_id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = $request->jwtUser();

        try {
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

            $resourceClass = $this->partnerContext->resourceFor(CancerTypeFilter::class);
            return response()->json([
                'data' => $resourceClass::make($filter)->resolve($request),
            ], 200);
        } catch (NotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)($jwtUser['id'] ?? 0),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/cancer-type-filters",
     *    operationId="createCancerTypeFilter",
     *    tags={"CancerTypeFilter"},
     *    summary="Create a cancer type filter",
     *    description="Creates a cancer type filter. The level is derived from the parent.",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *          required={"filter_id", "label"},
     *          @OA\Property(property="filter_id", type="string", example="0_0_2_60"),
     *          @OA\Property(property="label", type="string", example="C50 Breast"),
     *          @OA\Property(property="description", type="string", nullable=true, example=""),
     *          @OA\Property(property="category", type="string", nullable=true, example="icdOTopography"),
     *          @OA\Property(property="primary_group", type="string", nullable=true, example="cancer-type"),
     *          @OA\Property(property="count", type="string", example="0"),
     *          @OA\Property(property="parent_id", type="integer", nullable=true, example=3),
     *          @OA\Property(property="sort_order", type="integer", example=0)
     *       )
     *    ),
     *    @OA\Response(
     *       response="201",
     *       description="Created",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(property="data", type="object")
     *       )
     *    ),
     *    @OA\Response(response="400", description="Invalid argument(s)"),
     *    @OA\Response(response="401", description="Unauthorized")
     * )
     */
    public function store(CreateCancerTypeFilter $request): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $filter = $this->cancerTypeFilterService->create($request->validated());

            Auditor::log([
                'user_id' => (int)($jwtUser['id'] ?? 0),
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Cancer type filter ' . $filter['filter_id'] . ' created',
            ]);

            $resourceClass = $this->partnerContext->resourceFor(CancerTypeFilter::class);
            return $this->createdResponse($resourceClass::make($filter)->resolve($request));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)($jwtUser['id'] ?? 0),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/cancer-type-filters/{filter_id}",
     *    operationId="updateCancerTypeFilter",
     *    tags={"CancerTypeFilter"},
     *    summary="Replace a cancer type filter",
     *    description="Replaces all editable fields. Changing parent_id re-derives the level of the filter and its descendants.",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="filter_id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="string"),
     *       example="0_0_2_59"
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *          required={"filter_id", "label", "count", "parent_id", "sort_order"},
     *          @OA\Property(property="filter_id", type="string", example="0_0_2_59"),
     *          @OA\Property(property="label", type="string", example="C50 Breast"),
     *          @OA\Property(property="description", type="string", nullable=true, example=""),
     *          @OA\Property(property="category", type="string", nullable=true, example="icdOTopography"),
     *          @OA\Property(property="primary_group", type="string", nullable=true, example="cancer-type"),
     *          @OA\Property(property="count", type="string", example="0"),
     *          @OA\Property(property="parent_id", type="integer", nullable=true, example=3),
     *          @OA\Property(property="sort_order", type="integer", example=0)
     *       )
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(property="data", type="object")
     *       )
     *    ),
     *    @OA\Response(response="400", description="Invalid argument(s) or parent cycle"),
     *    @OA\Response(response="401", description="Unauthorized"),
     *    @OA\Response(response="404", description="Not found")
     * )
     */
    public function update(UpdateCancerTypeFilter $request, string $filter_id): JsonResponse
    {
        return $this->applyUpdate($request, $filter_id, $request->validated(), __FUNCTION__);
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/cancer-type-filters/{filter_id}",
     *    operationId="editCancerTypeFilter",
     *    tags={"CancerTypeFilter"},
     *    summary="Partially update a cancer type filter",
     *    description="Updates only the supplied fields. Changing parent_id re-derives the level of the filter and its descendants.",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="filter_id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="string"),
     *       example="0_0_2_59"
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *          @OA\Property(property="filter_id", type="string", example="0_0_2_59"),
     *          @OA\Property(property="label", type="string", example="C50 Breast"),
     *          @OA\Property(property="description", type="string", nullable=true, example=""),
     *          @OA\Property(property="category", type="string", nullable=true, example="icdOTopography"),
     *          @OA\Property(property="primary_group", type="string", nullable=true, example="cancer-type"),
     *          @OA\Property(property="count", type="string", example="0"),
     *          @OA\Property(property="parent_id", type="integer", nullable=true, example=3),
     *          @OA\Property(property="sort_order", type="integer", example=0)
     *       )
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(property="data", type="object")
     *       )
     *    ),
     *    @OA\Response(response="400", description="Invalid argument(s) or parent cycle"),
     *    @OA\Response(response="401", description="Unauthorized"),
     *    @OA\Response(response="404", description="Not found")
     * )
     */
    public function edit(EditCancerTypeFilter $request, string $filter_id): JsonResponse
    {
        return $this->applyUpdate($request, $filter_id, $request->validated(), __FUNCTION__);
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/cancer-type-filters/{filter_id}",
     *    operationId="deleteCancerTypeFilter",
     *    tags={"CancerTypeFilter"},
     *    summary="Delete a cancer type filter",
     *    description="Deletes the filter and all of its descendants.",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="filter_id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="string"),
     *       example="0_0_2_59"
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success")
     *       )
     *    ),
     *    @OA\Response(response="401", description="Unauthorized"),
     *    @OA\Response(response="404", description="Not found")
     * )
     */
    public function destroy(Request $request, string $filter_id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            if (!$this->cancerTypeFilterService->delete($filter_id)) {
                throw new NotFoundException('Cancer type filter not found');
            }

            Auditor::log([
                'user_id' => (int)($jwtUser['id'] ?? 0),
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Cancer type filter ' . $filter_id . ' deleted',
            ]);

            return $this->okResponse(null);
        } catch (NotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)($jwtUser['id'] ?? 0),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    private function applyUpdate(Request $request, string $filterId, array $data, string $action): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $filter = $this->cancerTypeFilterService->update($filterId, $data);
            if (!$filter) {
                throw new NotFoundException('Cancer type filter not found');
            }

            Auditor::log([
                'user_id' => (int)($jwtUser['id'] ?? 0),
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . $action,
                'description' => 'Cancer type filter ' . $filterId . ' updated',
            ]);

            $resourceClass = $this->partnerContext->resourceFor(CancerTypeFilter::class);
            return $this->okResponse($resourceClass::make($filter)->resolve($request));
        } catch (NotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (BadRequestException $e) {
            return $this->badRequestResponse($e->getMessage());
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)($jwtUser['id'] ?? 0),
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . $action,
                'description' => $e->getMessage(),
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }
}
