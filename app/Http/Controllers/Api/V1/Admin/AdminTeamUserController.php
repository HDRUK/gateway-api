<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Auditor;
use Exception;
use App\Models\User;
use App\Models\TeamHasUser;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Models\TeamUserHasNotification;
use App\Models\TeamUserHasRole;
use App\Http\Requests\TeamUser\DeleteTeamUser;

class AdminTeamUserController extends Controller
{
    /**
     * Remove a super-user from a team.
     *
     * Reuses the same team pivot cleanup as TeamUserController::destroy(),
     * but does not apply the "last custodian.team.admin" guard, since
     * super-users are not subject to that team-level restriction.
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
            $user = User::where('id', $userId)->first();

            if (!$user) {
                throw new NotFoundException();
            }

            if (!$user->is_admin) {
                return response()->json([
                    'message' => 'This endpoint is for removing super-users only — use the standard team-member removal endpoint for other users.',
                ], 400);
            }

            $teamHasUsers = TeamHasUser::where([
                'team_id' => $teamId,
                'user_id' => $userId,
            ])->first();

            if (!$teamHasUsers) {
                throw new NotFoundException();
            }

            TeamUserHasNotification::where([
                'team_has_user_id' => $teamHasUsers->id,
            ])->delete();

            TeamUserHasRole::where([
                'team_has_user_id' => $teamHasUsers->id,
            ])->delete();

            TeamHasUser::where([
                'team_id' => $teamHasUsers->team_id,
                'user_id' => $teamHasUsers->user_id,
            ])->delete();

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
