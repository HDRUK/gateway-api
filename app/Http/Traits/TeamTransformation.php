<?php

namespace App\Http\Traits;

use App\Models\User;
use App\Models\TeamHasUser;
use App\Models\Notification;
use App\Models\TeamHasNotification;
use App\Models\TeamUserHasNotification;

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
                'created_at' => $team['created_at'],
                'updated_at' => $team['updated_at'],
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
                'mongo_object_id' => $team['mongo_object_id'],
                'notification_status' => $team['notification_status'],
                'is_question_bank' => $team['is_question_bank'],
            ];

            $tmpUser = [];
            foreach ($team['users'] as $user) {
                $tmp = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'firstname' => $user['firstname'],
                    'lastname' => $user['lastname'],
                    'email' => $user['email'],
                    'secondary_email' => $user['secondary_email'],
                    'preferred_email' => $user['preferred_email'],
                    'providerid' => $user['providerid'],
                    'provider' => $user['provider'],
                    'created_at' => $user['created_at'],
                    'updated_at' => $user['updated_at'],
                    'deleted_at' => $user['deleted_at'],
                    'sector_id' => $user['sector_id'],
                    'bio' => $user['bio'],
                    'domain' => $user['domain'],
                    'link' => $user['link'],
                    'orcid' => $user['orcid'],
                    'contact_feedback' => $user['contact_feedback'],
                    'contact_news' => $user['contact_news'],
                    'mongo_id' => $user['mongo_id'],
                    'mongo_object_id' => $user['mongo_object_id'],
                    'terms' => $user['terms'],
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

    public function getTeamNotifications($team, int $teamId, int $userId)
    {
        $response = $team;
        $user = User::where('id', $userId)->first();
        $user['notification_status'] = false;

        $teamHasUser = TeamHasUser::where([
            'team_id' => $teamId,
            'user_id' => $userId,
        ])->first();

        if ($teamHasUser) {
            $teamHasUserId = $teamHasUser->id;
            $teamUserHasNotification = TeamUserHasNotification::where([
                'team_has_user_id' => $teamHasUserId,
            ])->first();
            if ($teamUserHasNotification) {
                $userNotification = Notification::where('id', $teamUserHasNotification->notification_id)->first();
                $userNotificationStatus = $userNotification->enabled;
                $user['notification_status'] = $userNotificationStatus;
            }
        }

        $response->user = $user;

        return $response;
    }
}
