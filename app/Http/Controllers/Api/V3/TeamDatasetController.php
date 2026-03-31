<?php

namespace App\Http\Controllers\Api\V3;

use Config;
use Auditor;
use Exception;
use App\Models\Team;
use App\Models\Dataset;
use Illuminate\Http\JsonResponse;
use App\Services\DatasetService;
use App\Http\Controllers\Controller;
use App\Http\Traits\CheckAccess;
use App\Http\Traits\RequestTransformation;
use App\Http\Requests\V2\Dataset\UpdateTeamDataset;

/**
 * V3 TeamDataset controller.
 *
 * Thin wrapper around DatasetService::update() for the team-scoped PUT
 * endpoint. Delegates all business logic (TRASER translation, delta
 * versioning, job dispatch) to the service layer.
 *
 * The v2 equivalent keeps the legacy overwrite behaviour for backwards
 * compatibility with existing third-party integrations.
 */
class TeamDatasetController extends Controller
{
    use CheckAccess;
    use RequestTransformation;

    public function __construct(
        private readonly DatasetService $datasetService,
    ) {
    }

    /**
     * PUT /api/v3/teams/{teamId}/datasets/{id}
     *
     * Update team dataset metadata using the delta-versioning strategy.
     */
    public function update(UpdateTeamDataset $request, int $teamId, int $id): JsonResponse
    {
        $input   = $request->all();
        list($userId, $appTeamId, $createOrigin) = $this->getAccessorUserAndTeam($request);
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $dataset = Dataset::where('id', $id)->first();
        $this->checkAccess($input, $dataset->team_id, null, 'team', $request->header());

        try {
            $team = Team::where('id', $teamId)->first();

            $input['team_id']             = $teamId;
            $input['status']              = $request['status'];
            $input['is_cohort_discovery'] = $input['is_cohort_discovery'] ?? false;

            $versionNumber = $this->datasetService->update(
                dataset: $dataset,
                input: $input,
                userId: $jwtUser['id'] ?? $userId,
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
                'description' => 'Dataset ' . $id . ' updated to version ' . ($versionNumber + 1),
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
}
