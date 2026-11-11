<?php

namespace App\Http\Traits;

use Config;
use App\Models\User;
use App\Models\Alias;
use App\Models\TeamHasUser;
use App\Models\Notification;
use App\Models\TeamHasAlias;
use App\Models\TeamHasNotification;
use App\Models\TeamUserHasNotification;
use Illuminate\Support\Collection;
use App\Models\Role;
use App\Models\Team;

trait TeamTransformation
{
    /**
     * Convert output for multiple Teams
     *
     * @param Collection<int, Team> $teams
     * @return array
     */
    public function getTeams(Collection $teams): array
    {
        if ($teams->isEmpty()) {
            return [];
        }

        $response = [];

        $teamIds = $teams->pluck('id')->all();

        $teamHasUserIds = $teams->map(fn($team) => $this->getTeamHasUsers($team))->flatten()->all();

        $rolesByTeamHasUserId = TeamHasUser::whereIn('id', $teamHasUserIds)
            ->with('roles', 'roles.permissions')
            ->get()
            ->keyBy('id');

        unset($teamHasUserIds);

        $notificationsByTeamId = TeamHasNotification::whereIn('team_id', $teamIds)
            ->get()
            ->groupBy('team_id');

        $notificationIds = $notificationsByTeamId->flatten()->pluck('notification_id')->unique()->values()->all();

        $notificationsById = Notification::whereIn('id', $notificationIds)->get()->keyBy('id');

        $aliasIdsByTeamId = TeamHasAlias::whereIn('team_id', $teamIds)
            ->get()
            ->groupBy('team_id');

        $allAliasIds = $aliasIdsByTeamId->flatten()->pluck('alias_id')->unique()->values()->all();

        $aliasesById = Alias::whereIn('id', $allAliasIds)->get()->keyBy('id');

        return $teams->map(function ($team) 
            use (
                $notificationsByTeamId, 
                $notificationsById, 
                $aliasIdsByTeamId,
                $aliasesById,
                $rolesByTeamHasUserId,
            ) {

            $notificationIds = $notificationsByTeamId->get($team['id'], collect())->pluck('notification_id')->unique()->values()->all();

            $notifications = $notificationsById->filter(fn ($notification, $key) => in_array($key, $notificationIds));

            $teamAliasIds = $aliasIdsByTeamId->get($team['id'], collect())->pluck('alias_id')->all();
            $aliases = $aliasesById->only($teamAliasIds)->sortBy('id')->values();

            return $this->hydrateTeam(
                team: $team,
                teamUserRoles: $rolesByTeamHasUserId,
                notifications: $notifications,
                aliases: $aliases
            );
        })->toArray();
    }

    /**
     * Convert output for single team
     *
     * @param Team $team
     * @return array
     */
    public function getTeam(?Team $team): array
    {
        if (!$team) {
            return [];
        }
           
        $teamHasUserIds = $this->getTeamHasUsers($team);

        $rolesByTeamHasUserId = TeamHasUser::whereIn('id', $teamHasUserIds)
            ->with('roles', 'roles.permissions')
            ->get()
            ->keyBy('id');

        unset($teamHasUserIds);

        $notificationIds = TeamHasNotification::where('team_id', $team->id)
            ->pluck('notification_id')
            ->unique()
            ->values()
            ->all();

        $notifications = Notification::whereIn('id', $notificationIds)->get();

        $aliasIds = TeamHasAlias::where('team_id', $team->id)
            ->pluck('alias_id')
            ->unique()
            ->values()
            ->all();

        $aliases = Alias::whereIn('id', $aliasIds)->get();

        return $this->hydrateTeam(
            team: $team,
            teamUserRoles: $rolesByTeamHasUserId,
            notifications: $notifications,
            aliases: $aliases,
        );
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

        unset(
            $user,
            $teamHasUser,
        );

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

    private function getTeamHasUsers(Team $team): array
    {
        $teamHasUserIds = [];

        $teamHasUserIds = $team->users->map(function (User $user) {
            if ($user->is_admin) {
                return null;
            }

            return (int) $user->pivot->id;
        })->filter()->toArray();

        return $teamHasUserIds;
    }

    /**
     * @param Collection<int, TeamHasUser> $teamUserRoles
     * @param Collection<int, Notification> $notifications
     * @param Collection<int, Alias> $aliases
     */
    private function hydrateTeam(
        Team $team,
        Collection $teamUserRoles,
        Collection $notifications,
        Collection $aliases,
    ): array {

        // to perform model casts
        $teamArray = $team->toArray();

        $tmpTeam = [
            'id' => $teamArray['id'],
            'pid' => $teamArray['pid'],
            'created_at' => $teamArray['created_at'],
            'updated_at' => $teamArray['updated_at'],
            'name' => $teamArray['name'],
            'enabled' => $teamArray['enabled'],
            'allows_messaging' => $teamArray['allows_messaging'],
            'workflow_enabled' => $teamArray['workflow_enabled'],
            'access_requests_management' => $teamArray['access_requests_management'],
            'uses_5_safes' => $teamArray['uses_5_safes'],
            'is_admin' => $teamArray['is_admin'],
            'member_of' => $teamArray['member_of'],
            'contact_point' => $teamArray['contact_point'],
            'application_form_updated_by' => $teamArray['application_form_updated_by'],
            'application_form_updated_on' => $teamArray['application_form_updated_on'],
            'notification_status' => $teamArray['notification_status'],
            'is_question_bank' => $teamArray['is_question_bank'],
            'is_provider' => $teamArray['is_provider'],
            'url' => $teamArray['url'],
            'introduction' => $teamArray['introduction'],
            'dar_modal_header' => $teamArray['dar_modal_header'],
            'dar_modal_content' => $teamArray['dar_modal_content'],
            'dar_modal_footer' => $teamArray['dar_modal_footer'],
            'team_logo' => (is_null($teamArray['team_logo']) || strlen(trim($teamArray['team_logo'])) === 0) ? '' : (preg_match('/^https?:\/\//', $teamArray['team_logo']) ? $teamArray['team_logo'] : Config::get('services.media.base_url') . $teamArray['team_logo']),
            'service' => $teamArray['service'],
        ];

        $tmpTeam['users'] = $team->users->map(function (User $user) use ($teamUserRoles) {
            if ($user->is_admin) {
                return null;
            }

            return $this->hydrateTeamUser($user, $teamUserRoles);
        })->filter()->values()->toArray();

        $tmpTeam['notifications'] = $notifications->values()->all();
        $tmpTeam['aliases'] = $aliases->values()->all();

        return $tmpTeam;
    }

    /**
     * @param Collection<int, TeamHasUser> $teamUserRoles
     */
    private function hydrateTeamUser(User $user, Collection $teamUserRoles): array
    {
        // to perform model casts
        $userArray = $user->toArray();

        $tmpUser = [
            'id' => $userArray['id'],
            'name' => $userArray['name'],
            'firstname' => $userArray['firstname'],
            'lastname' => $userArray['lastname'],
            'email' => $this->maskEmail($userArray['email']),
            'secondary_email' => $this->maskEmail($userArray['secondary_email']),
            'preferred_email' => $userArray['preferred_email'],
            'provider' => $userArray['provider'],
            'created_at' => $userArray['created_at'],
            'updated_at' => $userArray['updated_at'],
            'deleted_at' => $userArray['deleted_at'],
            'sector_id' => $userArray['sector_id'],
            'organisation' => $userArray['organisation'],
            'bio' => $userArray['bio'],
            'domain' => $userArray['domain'],
            'link' => $userArray['link'],
            'orcid' => $userArray['orcid'],
            'contact_feedback' => $userArray['contact_feedback'],
            'contact_news' => $userArray['contact_news'],
            'terms' => $userArray['terms'],
            'hubspot_id' => $userArray['hubspot_id'],
        ];

        $teamHasUserId = (int) $userArray['pivot']['id'];

        $tmpUser['roles'] = $teamUserRoles->get($teamHasUserId)->roles->map(fn(Role $role) => $role)->toArray();

        return $tmpUser;
    }
}
