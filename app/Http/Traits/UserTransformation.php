<?php

namespace App\Http\Traits;

use App\Models\TeamHasUser;
use App\Models\UserHasNotification;
use App\Models\Notification;

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
                'secondary_email' => $user['secondary_email'],
                'preferred_email' => $user['preferred_email'],
                'provider' => $user['provider'],
                'created_at' => $user['created_at'],
                'updated_at' => $user['updated_at'],
                'deleted_at' => $user['deleted_at'],
                'sector_id' => $user['sector_id'],
                'organisation' => $user['organisation'],
                'bio' => $user['bio'],
                'domain' => $user['domain'],
                'link' => $user['link'],
                'orcid' => $user['orcid'],
                'contact_feedback' => $user['contact_feedback'],
                'contact_news' => $user['contact_news'],
                'mongo_id' => $user['mongo_id'],
                'mongo_object_id' => $user['mongo_object_id'],
                'terms' => $user['terms'],
                'roles' => $user['roles'],
                'hubspot_id' => $user['hubspot_id'],
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
                    'is_question_bank' => $team['is_question_bank'],
                ];

                $teamHasUserId = (int)$team['pivot']['id'];

                $roles = TeamHasUser::where('id', $teamHasUserId)->with('roles')->get()->toArray();

                $tmpPerm = [];
                foreach ($roles[0]['roles'] as $role) {
                    $tmpPerm[] = $role;
                }
                $tmp['roles'] = $tmpPerm;

                $tmpTeam[] = $tmp;
                unset($tmp);
                unset($roles);
                unset($tmpPerm);
            }
            $tmpUser['teams'] = $tmpTeam;

            $notifications = UserHasNotification::where('user_id', $tmpUser['id'])->get()->toArray();
            $tmpNotification = [];
            foreach ($notifications as $value) {
                $notification = Notification::where('id', $value['notification_id'])->firstOrFail();
                $tmpNotification[] = $notification;
            }
            $tmpUser['notifications'] = $tmpNotification;

            // Added in to stop a singular /users/:id call returning an array for
            // the users part of the payload
            if (count($users) === 1) {
                $response = $tmpUser;
            } else {
                $response[] = $tmpUser;
            }

            unset($tmpTeam);
            unset($tmpUser);
            unset($notifications);
            unset($tmpNotification);
        }

        return $response;
    }

    private function maskEmail(string|null $email)
    {
        if (is_null($email)) {
            return $email;
        }

        [$username, $domain] = explode('@', $email);
        $maskedUsername = substr($username, 0, 1) . str_repeat('*', max(strlen($username) - 2, 1)) . substr($username, -1);
        $domainParts = explode('.', $domain);
        $domainName = $domainParts[0];
        $maskedDomain = substr($domainName, 0, 1) . str_repeat('*', max(strlen($domainName) - 2, 1)) . substr($domainName, -1);
        $maskedDomain .= '.' . implode('.', array_slice($domainParts, 1));
        $maskedEmail = $maskedUsername . '@' . $maskedDomain;

        return $maskedEmail;
    }
}
