<?php

namespace App\Rules;

use Closure;
use App\Models\Permission;
use Illuminate\Contracts\Validation\InvokableRule;

class PermissionRoleExists implements InvokableRule
{
    /**
     * Run the validation rule.
     *
     * @param string $attribute
     * @param mixed $value
     * @param \Closure $fail
     * @return void
     */
    public function __invoke(string $attribute, mixed $value, Closure $fail): void
    {
        $inputKey = explode('.', $attribute);
        $existingRoles = Permission::where('role', $inputKey[1])
                            ->first();
        if (!$existingRoles) {
            $fail('One or more of the roles in the permissions field do not exist.');
        }
    }
}
