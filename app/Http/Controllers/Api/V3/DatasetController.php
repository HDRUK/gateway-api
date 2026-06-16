<?php

namespace App\Http\Controllers\Api\V3;

use Config;
use Auditor;
use Exception;
use App\Models\Team;
use App\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\DatasetService;
use App\Http\Controllers\Controller;
use App\Http\Traits\CheckAccess;
use App\Http\Traits\RequestTransformation;
use App\Http\Requests\V2\Dataset\UpdateDataset;

/**
 * V3 Dataset controller.
 *
 * Exposes the delta-versioning update endpoint and the version history
 * read endpoints. All mutating logic lives in DatasetService.
 *
 * V2 endpoints remain unchanged for backwards compatibility with existing
 * third-party integrations.
 */
class DatasetController extends Controller
{
    use CheckAccess;
    use RequestTransformation;

    public function __construct(
        private readonly DatasetService $datasetService,
    ) {
    }

    /**
     * PUT /api/v3/datasets/{id}
     *
     * Update dataset metadata using the delta-versioning strategy: each save
     * creates an immutable version row (RFC 6902 patch for deltas, full
     * snapshot every SNAPSHOT_INTERVAL versions).
     */
    public function update(UpdateDataset $request, int $id): JsonResponse
    {
        $input = $request->all();
        list($userId, $teamId, $createOrigin) = $this->getAccessorUserAndTeam($request);
        $jwtUser = $input['jwt_user'] ?? [];
        $dataset = Dataset::where('id', $id)->first();
        $this->checkAccess($input, $dataset->team_id, null, 'team', $request->header());

        try {
            $team          = Team::where('id', $teamId)->first();
            $versionNumber = $this->datasetService->update(
                dataset: $dataset,
                input: $input,
                userId: $userId,
                teamId: $teamId,
                createOrigin: $createOrigin,
                elasticIndexing: $request->boolean('elastic_indexing', false),
                team: $team,
            );

            Auditor::log([
                'user_id'     => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
                'team_id'     => $teamId,
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Dataset ' . $id . ' updated to version ' . $versionNumber,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));

        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
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
     * GET /api/v3/datasets/{id}/versions
     *
     * Return a lightweight version index (id, version, title, short_title,
     * created_at) for a dataset. No metadata payloads are included; use
     * showVersion to retrieve the full reconstructed metadata for a specific
     * version.
     */
    public function listVersions(Request $request, int $id): JsonResponse
    {
        try {
            $dataset = Dataset::find($id);

            if (!$dataset) {
                return response()->json(['message' => 'Dataset not found'], 404);
            }

            $versions = $this->datasetService->listVersions($dataset);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Dataset ' . $id . ' versions listed',
            ]);

            return response()->json([
                'message' => 'success',
                'data'    => $versions,
            ]);

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
     * GET /api/v3/datasets/{id}/version/{version}
     *
     * Return the fully reconstructed metadata envelope for a specific
     * historical version. Delta rows are rolled up from the nearest
     * materialised snapshot, so the response is always a complete GWDM object.
     */
    public function showVersion(Request $request, int $id, int $version): JsonResponse
    {
        try {
            $dataset = Dataset::find($id);

            if (!$dataset) {
                return response()->json(['message' => 'Dataset not found'], 404);
            }

            $metadata = $this->datasetService->getVersion($dataset, $version);

            if ($metadata === null) {
                return response()->json(['message' => 'Version not found'], 404);
            }

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Dataset ' . $id . ' version ' . $version . ' retrieved',
            ]);

            return response()->json([
                'message' => 'success',
                'data'    => $metadata,
            ]);

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
