<?php

namespace App\Http\Traits;

use Exception;
use App\Models\TeamHasUser;
use App\Exceptions\UnauthorizedException;

trait CheckAccess
{
    private $jwtUser;
    private $jwtUserRolePerms;
    private $jwtMiddleware;
    private $jwtUserId;

    /**
     * Check Access
     *
     * @param array $input
     * @param integer $dbTeamId This is the Team Id coming from database
     * @param integer $dbUserId This is the User Id coming from database
     * @param string $checkType Expect like values team or user
     * @return void
     */
    public function checkAccess(
        array $input = [],
        int $dbTeamId = null, 
        int $dbUserId = null, 
        string $checkType = null
        )
    {
        $this->jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        if (!count($this->jwtUser)) {
            throw new Exception('Insufficient information');
        }
        $this->jwtUserRolePerms = array_key_exists('role_perms', $this->jwtUser) ? $this->jwtUser['role_perms'] : [];
        $this->jwtMiddleware = array_key_exists('middleware', $input) ? $input['middleware'] : [];
        $this->jwtUserId = (int)$this->jwtUser['id'];
        $this->dbTeamId = (int)$dbTeamId;
        $this->dbUserId = (int)$dbUserId;

        if ($checkType === 'team') {
            return $this->checkAccessTeam();
        }

        if ($checkType === 'user') {
            return $this->checkAccessUser();
        }

        throw new Exception('Insufficient information');
    }

    private function checkAccessTeam()
    {
        $checkTeamHasUser = TeamHasUser::where([
            'user_id' => $this->jwtUserId,
            'team_id' => $this->dbTeamId,
        ])->first();

        if (is_null($checkTeamHasUser)) {
            throw new Exception('Not enough permissions');
        }

        $teamRolePerms = array_key_exists($this->dbTeamId, $this->jwtUserRolePerms) ? $this->jwtUserRolePerms[$this->dbTeamId] : [];

        if (!count($teamRolePerms)) {
            throw new Exception('Not enough permissions');
        }

        $jwtMiddlewareRoles = array_key_exists('roles', $this->jwtMiddleware) ? $this->jwtMiddleware['roles'] : [];
        $jwtMiddlewarePerms = array_key_exists('perms', $this->jwtMiddleware) ? $this->jwtMiddleware['perms'] : [];

        if (count($jwtMiddlewareRoles)) {
            $checkingRoles = array_diff($jwtMiddlewarePerms, $teamRolePerms['roles']);
            if (!empty($checkingRoles)) {
                throw new UnauthorizedException();
            }
        }

        if (count($jwtMiddlewarePerms)) {
            $checkingRoles = array_diff($jwtMiddlewarePerms, $teamRolePerms['roles']);
            if (!empty($checkingRoles)) {
                throw new UnauthorizedException();
            }
        }

        return true;
    }

    private function checkAccessUser()
    {
        if ($this->jwtUserId === $this->dbUserId) {
            throw new Exception('Not enough permissions');
        }

        return true;
    }
}