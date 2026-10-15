<?php

namespace App\Observers;

use App\Exceptions\BadRequestException;
use App\Models\Role;
use App\Models\User;
use App\Models\UserHasRole;

class UserObserver
{
    /**
     * Handle the User "saving" event.
     */
    public function saving(User $user): void
    {
        $becomingAdmin = $user->isDirty('is_admin') && $user->is_admin;
        $emailChangingWhilePrivileged = $user->isDirty('email') && $this->isPrivileged($user);

        if (!$becomingAdmin && !$emailChangingWhilePrivileged) {
            return;
        }

        $domain = strtolower(substr(strrchr($user->email, '@') ?: '', 1));
        $allowed = array_map('strtolower', config('gateway.privileged_email_domains', []));

        if (!in_array($domain, $allowed, true)) {
            throw new BadRequestException(
                "Cannot grant admin privileges to '{$user->email}': not an allow-listed domain."
            );
        }
    }

    private function isPrivileged(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        if (!$user->exists) {
            return false;
        }

        $roleIds = Role::where('is_privileged', true)->pluck('id');

        return UserHasRole::where('user_id', $user->id)->whereIn('role_id', $roleIds)->exists();
    }
}
