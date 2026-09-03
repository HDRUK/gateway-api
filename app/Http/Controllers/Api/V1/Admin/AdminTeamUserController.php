<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Auditor;
use Exception;
use Illuminate\Http\JsonResponse;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnprocessableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TeamUser\DeleteTeamUser;
use App\Services\AdminUserService;

class AdminTeamUserController extends Controller
{
    public function __construct(private readonly AdminUserService $adminUserService)
    {
    }

    /**
     * Remove a super-user from a team.
     *
     * Delegates entirely to AdminUserService::removeUserFromTeams() -
     * the "target must be a super-user" check and the pivot cleanup live
     * there once, rather than being duplicated between this controller and
     * the bulk remove-from-teams endpoint.
     *
     * @param \App\Http\Requests\TeamUser\DeleteTeamUser $request
     * @param integer $teamId
     * @param integer $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(DeleteTeamUser $request, int $teamId, int $userId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $results = $this->adminUserService->removeUserFromTeams($userId, [$teamId]);

            if (($results[$teamId] ?? null) !== 'removed') {
                throw new NotFoundException();
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'target_user_id' => $userId,
                'target_team_id' => $teamId,
                'action_type' => 'REMOVE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Super-user was removed from team',
            ]);

            return response()->json([
                'message' => 'success',
            ], 200);
        } catch (UnprocessableException $e) {
            Auditor::log([
                'user_id' => (int)($jwtUser['id'] ?? 0),
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw $e;
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)($jwtUser['id'] ?? 0),
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }
}
