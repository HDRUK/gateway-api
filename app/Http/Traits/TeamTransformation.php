<?php

namespace App\Http\Traits;

use App\Models\TeamHasUser;
use App\Models\TeamHasNotification;
use App\Models\Notification;

trait TeamTransformation
{
    /**
     * Convert output for Team
     *
     * @param array $teams
     * @return array
     */
    public function getTeams(array $teams): array
    {
        $response = [];
        
        foreach ($teams as $team) {
            $tmpTeam = [
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

            $tmpUser = [];
            foreach ($team['users'] as $user) {
                $tmp = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'firstname' => $user['firstname'],
                    'lastname' => $user['lastname'],
                    'email' => $user['email'],
                    'seconday_email' => $user['seconday_email'],
                    'preferred_email' => $user['preferred_email'],
                    'providerid' => $user['providerid'],
                    'provider' => $user['provider'],
                    'created_at' => $user['created_at'],
                    'updated_at' => $user['updated_at'],
                    'deleted_at' => $user['deleted_at'],
                ];

                $teamHasUserId = (int) $user['pivot']['id'];

                $roles = TeamHasUser::where('id', $teamHasUserId)->with('roles', 'roles.permissions')->get()->toArray();

                $tmpPerm = [];
                foreach ($roles[0]['roles'] as $role) {
                    $tmpPerm[] = $role;
                }
                $tmp['roles'] = $tmpPerm;

                $tmpUser[] = $tmp;
                unset($tmp);
                unset($tmpPerm);
            }

            $tmpTeam['users'] = $tmpUser;

            $notifications = TeamHasNotification::where('team_id', $tmpTeam['id'])->get()->toArray();
            $tmpNotification = [];
            foreach ($notifications as $value) {
                $notification = Notification::where('id', $value['notification_id'])->firstOrFail();
                $tmpNotification[] = $notification;
            }
            $tmpTeam['notifications'] = $tmpNotification;

            $response[] = $tmpTeam;
            unset($tmpTeam);
            unset($tmpUser);
            unset($tmpNotification);
        }

        if (count($response) > 1) {
            return $response;
        }

        return $response[0];
    }
}
