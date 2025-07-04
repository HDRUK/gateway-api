<?php

namespace App\Http\Traits;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use App\Models\TeamHasUser;
use App\Models\UserHasRole;
use App\Models\CohortRequest;
use App\Models\TeamUserHasRole;
use App\Models\RoleHasPermission;
use App\Exceptions\UnauthorizedException;
use App\Models\CohortRequestHasPermission;

trait UserRolePermissions
{
    private function checkUserPermissions($payloadRoles, array $rolePerms, $teamId, array $checkPermissions)
    {
        $currentUserRoles = array_unique(array_merge(
            $rolePerms['extra']['roles'],
            $rolePerms['teams'][(string) $teamId]['roles']
        ));
        if (in_array('custodian.team.admin', $currentUserRoles) ||
            in_array('hdruk.custodian', $currentUserRoles)) {
            return true;
        }

        $currentUserPermissions = array_unique(array_merge(
            $rolePerms['extra']['perms'],
            $rolePerms['teams'][(string) $teamId]['perms']
        ));

        foreach ($checkPermissions as $perm => $roles) {
            foreach ($roles as $role) {
                if (in_array($role, $payloadRoles) && !in_array($perm, $currentUserPermissions)) {
                    throw new UnauthorizedException('Not Enough Permissions.');
                }

                $payloadRoles = array_filter($payloadRoles, fn ($m) => $m != $role);
            }
        }

        if (count($payloadRoles)) {
            throw new UnauthorizedException('Not Enough Permissions2.');
        }
    }

    private function getUserRolePerms(int $userId, ?int $teamId = null): array
    {
        $return = [];
        // extra - user roles/perms outside team
        $extraRoles = $this->getUserRolesNoTeam($userId);
        $return['cohort']['roles'] = $this->getCohortUserRoles($userId);
        $return['extra']['roles'] = $extraRoles;
        $return['extra']['perms'] = $this->getPermsFromRoles($extraRoles);

        // teams - user roles/perms by team
        $roleWithTeams = $this->getUserRolesWithTeams($userId);

        if ($teamId) {
            $return['teams'][$teamId]['roles'] = $roleWithTeams;
            $return['teams'][$teamId]['perms'] = $this->getPermsFromRoles($roleWithTeams);
        } else {
            foreach ($roleWithTeams as $team => $roleWithTeam) {
                $return['teams'][$team]['roles'] = $roleWithTeam;
                $return['teams'][$team]['perms'] = $this->getPermsFromRoles($roleWithTeam);
            }
        }

        // summary - user roles/perms
        $roles = $return['extra']['roles'];
        $perms = $this->getPermsFromRoles($return['extra']['roles']);
        foreach ($roleWithTeams as $team => $roleWithTeam) {
            $roles = array_merge($roles, $return['teams'][$team]['roles']);
            $perms = array_merge($perms, $return['teams'][$team]['perms']);
        }

        $return['summary']['roles'] = array_unique($roles);
        $return['summary']['perms'] = array_unique($perms);

        return $return;
    }

    private function getPermsFromRoles(array $roles): array
    {
        if (!$roles) {
            return [];
        }

        $roleIds = Role::whereIn('name', $roles)->pluck('id')->toArray();
        $rolePermIds = RoleHasPermission::whereIn('role_id', $roleIds)->pluck('permission_id')->toArray();
        return Permission::whereIn('id', $rolePermIds)->pluck('name')->toArray();
    }

    private function getUserIsAdmin(int $userId): bool
    {
        $user = User::where('id', $userId)->first();
        return (bool) $user->is_admin;
    }

    private function getUserRolesNoTeam(int $userId): array
    {
        $userRoleIds = UserHasRole::where('user_id', $userId)->orderBy('role_id')->pluck('role_id')->toArray();

        if (!$userRoleIds) {
            return [];
        }

        return Role::whereIn('id', $userRoleIds)->pluck('name')->toArray();
    }

    private function getUserRolesWithTeams(int $userId): array
    {
        $return = [];
        $userTeams = TeamHasUser::where('user_id', $userId)->get();

        if (!$userTeams) {
            return [];
        }

        foreach ($userTeams as $userTeam) {
            $teamId = $userTeam->team_id;
            $userTeamRoleIds = TeamUserHasRole::where('team_has_user_id', $userTeam->id)->pluck('role_id')->toArray();
            $roles = Role::whereIn('id', $userTeamRoleIds)->pluck('name')->toArray();
            $return[$teamId] = $roles;
        }

        return $return;
    }

    private function getCohortUserRoles(int $userId): array
    {
        $cohortRequest = CohortRequest::where([
            'user_id' => $userId,
            'request_status' => 'APPROVED',
        ])->first();

        if (!$cohortRequest) {
            return [];
        }

        $cohortRequestRoleIds = CohortRequestHasPermission::where([
            'cohort_request_id' => $cohortRequest->id
        ])->pluck('permission_id')->toArray();

        $cohortRequestRoles = Permission::whereIn('id', $cohortRequestRoleIds)->pluck('name')->toArray();

        return $cohortRequestRoles;
    }
}
