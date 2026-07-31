<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use Auditor;
use App\Http\Controllers\Controller;
use App\Jobs\ReindexTypesenseEntity;
use App\SearchProviders\HDRUK;
use App\Services\TypesenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Pennant\Feature;

class AdminSearchController extends Controller
{
    /**
     * Features an admin is allowed to flip via toggleFeature() — an explicit
     * allow-list so this endpoint can't be used to activate arbitrary Pennant
     * features by name.
     */
    private const TOGGLEABLE_FEATURES = ['TypesenseSearch', 'V2_SearchAggregation'];

    /**
     * @OA\Get(
     *     path="/api/v1/admin/search/status",
     *     operationId="fetch_admin_search_status",
     *     summary="Get Typesense collection status for every onboarded search entity",
     *     tags={"Admin-Search"},
     *     security={{"jwt": {}}},
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function status(TypesenseService $typesense): JsonResponse
    {
        try {
            $entities = [];

            foreach (HDRUK::typesenseModelMap() as $entity => $modelClass) {
                $model = new $modelClass();
                $collectionName = $model->searchableAs();
                $exists = $typesense->collectionExists($collectionName);

                $entities[] = [
                    'entity'          => $entity,
                    'model'           => $modelClass,
                    'collection'      => $collectionName,
                    'collectionExists' => $exists,
                    'documentCount'   => $exists ? $typesense->documentCount($collectionName) : 0,
                    'databaseCount'   => $modelClass::count(),
                    'eligibleCount'   => $modelClass::indexEligible()->count(),
                    'facetFields'     => config("typesense.facet_map.{$entity}", ''),
                ];
            }

            return response()->json([
                'message' => 'success',
                'data'    => [
                    'entities' => $entities,
                    'features' => collect(self::TOGGLEABLE_FEATURES)
                        ->mapWithKeys(fn ($feature) => [$feature => Feature::active($feature)]),
                ],
            ], 200);
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
     * @OA\Post(
     *     path="/api/v1/admin/search/reindex",
     *     operationId="create_admin_search_reindex",
     *     summary="Queue a drop+recreate+import of a search entity's Typesense collection",
     *     tags={"Admin-Search"},
     *     security={{"jwt": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="entity", type="string", example="datasets")
     *         )
     *     ),
     *     @OA\Response(response=202, description="Reindex queued"),
     *     @OA\Response(response=422, description="Unknown entity")
     * )
     */
    public function reindex(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'entity' => ['required', 'string', 'in:' . implode(',', array_keys(HDRUK::typesenseModelMap()))],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Unknown entity',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $entity = $request->input('entity');

            ReindexTypesenseEntity::dispatch($entity);

            Auditor::log([
                'action_type' => 'POST',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => "Queued Typesense reindex for entity '{$entity}'",
            ]);

            return response()->json([
                'message' => 'queued',
                'entity'  => $entity,
            ], 202);
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
     * @OA\Post(
     *     path="/api/v1/admin/search/feature",
     *     operationId="update_admin_search_feature",
     *     summary="Activate or deactivate a search-related Pennant feature flag",
     *     tags={"Admin-Search"},
     *     security={{"jwt": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="feature", type="string", example="TypesenseSearch"),
     *             @OA\Property(property="enabled", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=422, description="Unknown feature")
     * )
     */
    public function toggleFeature(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'feature' => ['required', 'string', 'in:' . implode(',', self::TOGGLEABLE_FEATURES)],
                'enabled' => ['required', 'boolean'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Unknown feature',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $feature = $request->input('feature');
            $enabled = (bool) $request->input('enabled');

            $enabled ? Feature::activate($feature) : Feature::deactivate($feature);

            Auditor::log([
                'action_type' => 'POST',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => "Set feature '{$feature}' to " . ($enabled ? 'active' : 'inactive'),
            ]);

            return response()->json([
                'message' => 'success',
                'feature' => $feature,
                'enabled' => $enabled,
            ], 200);
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
