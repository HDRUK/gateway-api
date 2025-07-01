<?php

namespace App\Http\Traits;

use Exception;
use App\Models\Application;
use App\Models\TeamHasUser;
use App\Exceptions\UnauthorizedException;
use Illuminate\Http\Request;

trait CheckAccess
{
    /**
     * Check Access
     *
     * @param array $input
     * @param integer $dbTeamId This is the Team Id coming from database
     * @param integer $dbUserId This is the User Id coming from database
     * @param string $checkType Expect like values team or user
     * @return mixed
     */
    public function checkAccess(
        array $input = [],
        ?int $dbTeamId = null,
        ?int $dbUserId = null,
        ?string $checkType = null,
        ?array $headers = null,
    ) {
        // Check first for app access
        if (($headers) && isset($headers['x-application-id']) && isset($headers['x-client-id'])) {
            $application = Application::where('app_id', $headers['x-application-id'])
                ->where('client_id', $headers['x-client-id'])->first();

            if ($application) {
                if (($dbTeamId) && ($dbTeamId !== $application->team_id)) {
                    throw new UnauthorizedException(
                        'This Application is not allowed to interact with datasets from another team!'
                    );
                }
                return true;
            }
        }

        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        if (!count($jwtUser)) {
            throw new Exception('Insufficient information');
        }

        if ($jwtUser['is_admin']) {
            return true;
        }

        $jwtUserRolePerms = array_key_exists('role_perms', $jwtUser) ? (array_key_exists('teams', $jwtUser['role_perms']) ? $jwtUser['role_perms']['teams'] : []) : [];
        $jwtMiddleware = array_key_exists('middleware', $input) ? $input['middleware'] : [];
        $jwtUserId = (int)$jwtUser['id'];
        $dbTeamId = (int)$dbTeamId;
        $dbUserId = (int)$dbUserId;

        if ($checkType === 'team') {
            return $this->checkAccessTeam($jwtUserRolePerms, $jwtUserId, $dbTeamId, $jwtMiddleware);
        }

        if ($checkType === 'user') {
            return $this->checkAccessUser($jwtUserId, $dbUserId);
        }

        throw new UnauthorizedException();
    }

    /**
     * Check Access Collaborators
     *
     * @param array $input
     * @param array $dbUserIds This is an array of the User Ids coming from database
     * @return mixed
     */
    public function checkAccessCollaborators(
        array $input = [],
        ?array $dbUserIds = null
    ) {
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        if (!count($jwtUser)) {
            throw new Exception('Insufficient information');
        }

        if ($jwtUser['is_admin']) {
            return true;
        }

        $jwtUserId = (int)$jwtUser['id'];

        $access = $this->checkAccessCollaborator($jwtUserId, $dbUserIds);

        if ($access) {
            return true;
        }

        throw new UnauthorizedException();
    }

    private function checkAccessTeam($jwtUserRolePerms, $jwtUserId, $dbTeamId, $jwtMiddleware)
    {
        $checkTeamHasUser = TeamHasUser::where([
            'user_id' => $jwtUserId,
            'team_id' => $dbTeamId,
        ])->first();

        if (is_null($checkTeamHasUser)) {
            throw new UnauthorizedException();
        }

        $teamRolePerms = array_key_exists($dbTeamId, $jwtUserRolePerms) ? $jwtUserRolePerms[$dbTeamId] : [];

        if (!count($teamRolePerms)) {
            throw new UnauthorizedException();
        }

        $jwtMiddlewareRoles = array_key_exists('roles', $jwtMiddleware) ? $jwtMiddleware['roles'] : [];
        $jwtMiddlewarePerms = array_key_exists('perms', $jwtMiddleware) ? $jwtMiddleware['perms'] : [];

        if (count($jwtMiddlewareRoles)) {
            $checkingRoles = array_diff($jwtMiddlewareRoles, $teamRolePerms['roles']);
            if (!empty($checkingRoles)) {
                throw new UnauthorizedException();
            }

            return true;
        }

        if (count($jwtMiddlewarePerms)) {
            $checkingPerms = array_diff($jwtMiddlewarePerms, $teamRolePerms['perms']);
            if (!empty($checkingPerms)) {
                throw new UnauthorizedException();
            }
            return true;
        }
    }

    private function checkAccessUser($jwtUserId, $dbUserId)
    {
        if ($jwtUserId !== $dbUserId) {
            throw new UnauthorizedException();
        }

        return true;
    }

    private function checkAccessCollaborator($jwtUserId, $dbUserIds)
    {
        return in_array($jwtUserId, $dbUserIds);
    }

    public function getAccessorUserAndTeam(Request $request): array
    {
        $headers = $request->header();
        $input = $request->all();
        if (isset($headers['x-application-id']) && isset($headers['x-client-id'])) {
            $application = Application::where('app_id', $headers['x-application-id'])
                ->where('client_id', $headers['x-client-id'])->first();

            if ($application) {
                return [
                    $application->user_id,
                    $application->team_id,
                    'API',
                    'ACTIVE',
                    $application->id,
                ];
            }
        }

        return [
            $input['user_id'] ?? null,
            $input['team_id'] ?? null,
            $input['create_origin'] ?? 'MANUAL',
            $input['status'] ?? 'ACTIVE',
            null,
        ];
    }
}
