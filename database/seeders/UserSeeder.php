<?php

namespace Database\Seeders;

use Hash;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\UserHasRole;
use App\Models\TeamHasUser;
use App\Models\TeamUserHasRole;
use Illuminate\Database\Seeder;
use App\Models\TeamUserHasPermission;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create our super user account
        $user = User::factory()->create([
            'name' => 'HDRUK Super-User',
            'firstname' => 'HDRUK',
            'lastname' => 'Super-User',
            'email' => 'developers@hdruk.ac.uk',
            'provider' => 'service',
            'password' => Hash::make('Watch26Task?'),
            'is_admin' => true,
        ]);

        $role = Role::with('permissions')->where('name', 'hdruk.superadmin')->first();

        UserHasRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
        ]);

        User::factory(10)->create();
    }
}
