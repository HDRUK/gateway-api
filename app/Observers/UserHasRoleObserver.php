<?php

namespace App\Observers;

use App\Exceptions\BadRequestException;
use App\Models\Role;
use App\Models\User;
use App\Models\UserHasRole;

class UserHasRoleObserver
{
    /**
     * Handle the UserHasRole "creating" event.
     */
    public function creating(UserHasRole $pivot): void
    {
        $isPrivilegedRole = Role::where('id', $pivot->role_id)->value('is_privileged');
        if (!$isPrivilegedRole) {
            return;
        }

        $email = User::where('id', $pivot->user_id)->value('email') ?? '';
        $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));
        $allowed = array_map('strtolower', config('gateway.privileged_email_domains', []));

        if (!in_array($domain, $allowed, true)) {
            throw new BadRequestException(
                "Cannot grant role {$pivot->role_id} to user {$pivot->user_id}: email domain is not allow-listed."
            );
        }
    }
}
