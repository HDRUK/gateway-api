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
        $this->createUser('HDRUK', 'Super-User', 'developers@hdruk.ac.uk', 'Watch26Task?', true, ['hdruk.superadmin']);

        // Create our automation test users
        $this->createUser('HDR', 'Team-Admin', 'hdrteamadmin@gmail.com', 'Gateway#3177', false, ['custodian.team.admin']);
        $this->createUser('HDR', 'Team-Admin-Two', 'hdrgatea@gmail.com', 'December07*', false, ['custodian.team.admin']);
        
        $this->createUser('Dev', 'Eloper', 'hdrresearcher@gmail.com', 'London01!', false, ['developer']);
        $this->createUser('HDR', 'DarManager', 'hdrdarmanager@gmail.com', 'Gateway@123', false, ['hdruk.dar']);

        $this->createUser('Metadata', 'Manager', 'hdrmetadatamanager@gmail.com', 'Gateway@123', false, ['metadata.manager']);
        $this->createUser('Metadata', 'Editor', 'hdreditorhdr@gmail.com', 'London01!', false, ['metadata.editor']);

        $this->createUser('DarManager', 'MetadataManager', 'darmetadatamanager@gmail.com', 'London01!', false, [
            'dar.manager',
            'metadata.manager',
        ]);

        $this->createUser('Dar', 'Reviewer', 'hdrreviewer@gmail.com', 'Gateway@123', false, ['dar.reviewer']);

        User::factory(10)->create();
    }

    /**
     * Generically creates users per passed params
     * 
     * @param string $firstname     The firstname of the user to create
     * @param string $lastname      The lastname of the user to create
     * @param string $email         The email address of the user to create
     * @param string $password      The password of the user to create
     * @param bool $isAdmin         Whether this user being created is an admin
     * @param array $roles          The roles that should be applied to the user being created
     * 
     * @return void
     */
    private function createUser(string $firstname, string $lastname, string $email, string $password, bool $isAdmin, array $roles): void
    {
        $user = User::factory()->create([
            'name' => $firstname . ' ' . $lastname,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'provider' => 'service',
            'password' => Hash::make($password),
            'is_admin' => $isAdmin,
        ]);

        foreach ($roles as $role) {
            $r = Role::where('name', $role)->first();
            UserHasRole::create([
                'user_id' => $user->id,
                'role_id' => $r->id,
            ]);
        }
    }
}
