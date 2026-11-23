<?php

namespace App\Http\Controllers\Api\V3;

use Config;
use Auditor;
use Exception;
use App\Models\Dataset;
use App\Models\Team;
use App\Context\GwdmVersionContext;
use App\Context\PartnerContext;
use App\Enums\Partner;
use Laravel\Pennant\Feature;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Services\DatasetService;
use App\Http\Controllers\Controller;
use App\Http\Traits\CheckAccess;
use App\Http\Traits\RequestTransformation;
use App\Http\Requests\V3\Dataset\CreateDataset;
use App\Http\Requests\V3\Dataset\UpdateDataset;

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
        private readonly GwdmVersionContext $gwdmVersionContext,
    ) {
    }

    /**
     * POST /api/v3/teams/{teamId}/datasets
     */
    public function store(CreateDataset $request, int $teamId): JsonResponse
    {
        $jwtUser = $request->jwtUser();
        $input = $this->isSuperAdmin($jwtUser)
            ? $request->validated()
            : Arr::except($request->validated(), 'is_cohort_discovery');
        [$accessorUserId, , $createOrigin] = $this->getAccessorUserAndTeam($request);
        $userId = isset($jwtUser['id']) ? (int) $jwtUser['id'] : $accessorUserId;
        $team = Team::find($teamId);

        if (!$team) {
            return response()->json(['message' => 'Team not found'], Response::HTTP_NOT_FOUND);
        }

        $this->checkAccess($input, $team->id, null, 'team', $request->header());

        $partnerContext = Feature::active(PartnerContext::SELECTOR_FLAG)
            ? $request->header('x-partner-context')
            : null;
        $requestedPartner = $partnerContext !== null ? Partner::tryFrom($partnerContext) : null;

        if ($partnerContext !== null && $requestedPartner === null) {
            return response()->json([
                'message' => 'Unknown partner context',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (empty($input['metadata']['metadata']['summary']['title'])) {
            return response()->json(['message' => 'Title is required to save a dataset'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->datasetService->create(
                input: [
                    ...$input,
                    'user_id' => $userId,
                    'team_id' => $team->id,
                    'create_origin' => $createOrigin,
                ],
                team: $team,
                inputSchema: $request->query('input_schema'),
                inputVersion: $request->query('input_version'),
                targetGwdmVersion: $this->gwdmVersionContext->targetVersion(),
                partnerContext: ($requestedPartner ?? Partner::HDRUK)->value,
            );

            if (!$result['translated']) {
                return response()->json([
                    'message' => 'metadata is in an unknown format and cannot be processed',
                    'details' => $result['response'],
                ], Response::HTTP_BAD_REQUEST);
            }

            Auditor::log([
                'user_id'     => $userId,
                'team_id'     => $team->id,
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Dataset ' . $result['dataset_id'] . ' with version ' . $result['version_id'] . ' created',
            ]);

            return response()->json([
                'message' => 'created',
                'data'    => $result['dataset_id'],
                'version' => $result['version_id'],
            ], Response::HTTP_CREATED);
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
     * PUT /api/v3/datasets/{id}
     *
     * Update dataset metadata using the delta-versioning strategy: each save
     * creates an immutable version row (RFC 6902 patch for deltas, full
     * snapshot every SnapshotRule::INTERVAL versions).
     */
    public function update(UpdateDataset $request, int $id): JsonResponse
    {
        $jwtUser = $request->jwtUser();
        $input = $this->isSuperAdmin($jwtUser)
            ? $request->all()
            : $request->except('is_cohort_discovery');
        [$userId, , $createOrigin] = $this->getAccessorUserAndTeam($request);
        $dataset = Dataset::find($id);

        if (!$dataset) {
            return response()->json(['message' => 'Dataset not found'], 404);
        }

        $this->checkAccess($input, $dataset->team_id, null, 'team', $request->header());

        $partnerHeader = Feature::active(PartnerContext::SELECTOR_FLAG)
            ? $request->header('x-partner-context')
            : null;
        $requestedPartner = $partnerHeader !== null ? Partner::tryFrom($partnerHeader) : null;

        if ($partnerHeader !== null && $requestedPartner === null) {
            return response()->json([
                'message' => 'Unknown partner context',
            ], Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
        }

        try {
            $versionNumber = $this->datasetService->update(
                dataset: $dataset,
                input: $input,
                userId: $userId,
                createOrigin: $createOrigin,
                partnerContext: $requestedPartner,
                targetGwdmVersion: $this->gwdmVersionContext->targetVersion(),
                validateInput: $request->boolean('validate_input', true),
                validateOutput: $request->boolean('validate_output', true),
            );

            Auditor::log([
                'user_id'     => isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId,
                'team_id'     => $dataset->team_id,
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

            $versions = $this->datasetService->listPublishedVersions($dataset);

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

            $metadata = $this->datasetService->getPublishedVersion($dataset, $version);

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
