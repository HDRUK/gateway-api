<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Traits\TeamTransformation;
use App\Http\Traits\UserTransformation;
use App\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class CheckAccessMiddleware
{
    use UserTransformation;
    use TeamTransformation;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type='permissions|roles', string $data=''): Response
    {
        $input = $request->all();
        $userId = $input['jwt_user']['id'];
        $access = explode("|", $data);
        $teamId = $request->route('teamId');

        $user = User::where('id', $userId)->first();
        if ($user->is_admin) {
            return $next($request);
        }

        if (!count($access)) {
            throw new UnauthorizedException();
        }

        if ($teamId) {
            $currentUserRoles = $this->getRoles($teamId, $userId);
        } else {
            $currentUserRoles = $this->getRolesNoTeamId($userId);
        }

        if ($type === 'roles') {
            $checkingRoles = array_diff($access, $currentUserRoles);

            if (!empty($checkingRoles)) {
                throw new UnauthorizedException();
            }
        }

        if ($type === 'permissions') {
            $currentUserPermissions = $this->getAllPermissions($currentUserRoles);

            $checkingPermissions = array_diff($access, $currentUserPermissions);

            if (!empty($checkingPermissions)) {
                throw new UnauthorizedException();
            }
        }

        return $next($request);
    }

    public function getRolesNoTeamId(int $userId)
    {
        $return = [];

        $userTeams = User::where('id', $userId)->with(['teams', 'notifications'])->get()->toArray();
        $teams = $this->getUsers($userTeams);

        foreach ($teams['teams'] as $team) {
            foreach ($team['roles'] as $role) {
                $return[] = $role['name'];
            }
        }

        return array_unique($return);
    }

    public function getRoles(int $teamId, int $userId)
    {
        $return = [];

        $userTeam = Team::where('id', $teamId)->with(['users', 'notifications'])->get()->toArray();
        $teams = $this->getTeams($userTeam);

        foreach ($teams['users'] as $user) {
            if ($user['id'] === $userId) {
                foreach ($user['roles'] as $role) {
                    $return[] = $role['name'];
                }
            }
        }

        return array_unique($return);
    }

    public function getAllPermissions(array $roles)
    {
        $return = [];

        $rolePermissions = Role::with(['permissions'])->get()->toArray();

        foreach ($roles as $role) {
            foreach ($rolePermissions as $rolePermission) {
                if ($rolePermission['name'] === $role) {
                    foreach ($rolePermission['permissions'] as $permissions) {
                        $return[] = $permissions['name'];
                    }
                }
            }
        }

        return array_unique($return);
    }
}
