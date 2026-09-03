<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Auditor;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use App\Services\AdminUserService;
use App\Http\Requests\Admin\GetUserDeletionCheck;
use App\Http\Requests\Admin\RemoveUserFromTeams;
use App\Http\Requests\Admin\TransferAndDeleteUser;

class AdminUserController extends Controller
{
    public function __construct(private readonly AdminUserService $adminUserService)
    {
    }

    /**
     * List every user as a reassignment-picker option: name and team
     * membership, no email address.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function picker(): JsonResponse
    {
        return response()->json([
            'data' => $this->adminUserService->getPickerOptions(),
        ], 200);
    }

    /**
     * Count how many entities each of the given users owns, in a small
     * fixed number of aggregate queries rather than one per user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function ownedEntityCounts(): JsonResponse
    {
        $userIds = array_map('intval', request('user_ids', []));

        return response()->json([
            'data' => $this->adminUserService->getOwnedEntityCounts($userIds),
        ], 200);
    }

    /**
     * Remove a super-user from any number of teams in one pass.
     *
     * @param \App\Http\Requests\Admin\RemoveUserFromTeams $request
     * @param integer $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeFromTeams(RemoveUserFromTeams $request, int $userId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $teamIds = $input['team_ids'] ?? [];

        try {
            $results = $this->adminUserService->removeUserFromTeams($userId, $teamIds);

            Auditor::log([
                'user_id' => (int)($jwtUser['id'] ?? 0),
                'target_user_id' => $userId,
                'action_type' => 'REMOVE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Super-user was removed from teams: ' . implode(', ', $teamIds),
            ]);

            return response()->json([
                'data' => $results,
            ], 200);
        } catch (ValidationException $e) {
            Auditor::log([
                'user_id' => (int)($jwtUser['id'] ?? 0),
                'target_user_id' => $userId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)($jwtUser['id'] ?? 0),
                'target_user_id' => $userId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * List every entity linked to a user that must be reassigned or
     * deleted before that user can be safely deleted.
     *
     * @param \App\Http\Requests\Admin\GetUserDeletionCheck $request
     * @param integer $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deletionCheck(GetUserDeletionCheck $request, int $userId): JsonResponse
    {
        try {
            $linkedEntities = $this->adminUserService->getLinkedEntities($userId);

            return response()->json([
                'data' => $linkedEntities,
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $userId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Apply the given reassign/delete decisions for every entity linked to
     * a user, then hard-delete the user.
     *
     * @param \App\Http\Requests\Admin\TransferAndDeleteUser $request
     * @param integer $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function transferAndDelete(TransferAndDeleteUser $request, int $userId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $reassignments = $input['reassignments'] ?? [];

        try {
            $this->adminUserService->transferAndDeleteUser($userId, $reassignments);

            Auditor::log([
                'user_id' => (int)($jwtUser['id'] ?? 0),
                'target_user_id' => $userId,
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'User ' . $userId . ' was reassigned and hard-deleted',
            ]);

            return response()->json([
                'message' => 'success',
            ], 200);
        } catch (ValidationException $e) {
            Auditor::log([
                'user_id' => (int)($jwtUser['id'] ?? 0),
                'target_user_id' => $userId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)($jwtUser['id'] ?? 0),
                'target_user_id' => $userId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }
}
