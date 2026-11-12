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

class AdminSearchController extends Controller
{
    /**
     * @OA\Get(
     *      x={"internal"="true"},
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
     *      x={"internal"="true"},
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
}
