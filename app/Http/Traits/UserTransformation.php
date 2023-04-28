<?php

namespace App\Http\Traits;

use App\Models\TeamHasUser;

trait UserTransformation
{
    /**
     * Convert output for Users
     *
     * @param array $users
     * @return array
     */
    public function getUsers(array $users): array
    {
        $response = [];

        foreach ($users as $user) {
            $tmpUser = [
                'id' => $user['id'],
                'name' => $user['name'],
                'firstname' => $user['firstname'],
                'lastname' => $user['lastname'],
                'email' => $user['email'],
                'providerid' => $user['providerid'],
                'provider' => $user['provider'],
                'created_at' => $user['created_at'],
                'updated_at' => $user['updated_at'],
                'deleted_at' => $user['deleted_at'],
            ];

            $tmpTeam = [];
            foreach ($user['teams'] as $team) {
                $tmp = [
                    'id' => $team['id'],
                    'name' => $team['name'],
                    'enabled' => $team['enabled'],
                    'allows_messaging' => $team['allows_messaging'],
                    'workflow_enabled' => $team['workflow_enabled'],
                    'access_requests_management' => $team['access_requests_management'],
                    'uses_5_safes' => $team['uses_5_safes'],
                    'is_admin' => $team['is_admin'],
                    'member_of' => $team['member_of'],
                    'contact_point' => $team['contact_point'],
                    'application_form_updated_by' => $team['application_form_updated_by'],
                    'application_form_updated_on' => $team['application_form_updated_on'],
                ];

                $teamHasUserId = (int) $team['pivot']['id'];

                $permissions = TeamHasUser::where('id', $teamHasUserId)->with('permissions')->get()->toArray();

                $tmpPerm = [];
                foreach ($permissions[0]['permissions'] as $permission) {
                    $tmpPerm[] = $permission['role'];
                }
                $tmp['permissions'] = $tmpPerm;

                $tmpTeam[] = $tmp;
                unset($tmp);
                unset($tmpPerm);
            }
            $tmpUser['teams'] = $tmpTeam;
            $response[] = $tmpUser;
            unset($tmpTeam);
            unset($tmpUser);
        }

        return $response;
    }
}