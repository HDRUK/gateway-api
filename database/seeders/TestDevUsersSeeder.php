<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\TeamHasUser;
use App\Models\TeamUserHasRole;
use Illuminate\Support\Facades\Hash;

class TestDevUsersSeeder extends Seeder
{
    public function run()
    {
        if (!app()->environment(['local', 'development'])) {
            return;
        }
        $teamId = 21;

        $users = [
             ['name' => 'TeamAdmin', 'role_ids' => [7], 'email' => 'CypressTeamAdmin@hdruk.ac.uk'],
             ['name' => 'Developers', 'role_ids' => [8], 'email' => 'CypressDevelopers@hdruk.ac.uk'],
             ['name' => 'DarManager', 'role_ids' => [11], 'email' => 'CypressDarManager@hdruk.ac.uk'],
             ['name' => 'Reviewer', 'role_ids' => [12], 'email' => 'CypressReviewer@hdruk.ac.uk'],
             ['name' => 'MetaDataManager', 'role_ids' => [9], 'email' => 'CypressMetaDataManager@hdruk.ac.uk'],
             ['name' => 'EditorPermission', 'role_ids' => [10], 'email' => 'CypressEditorPermission@hdruk.ac.uk'],
             ['name' => 'DarMetadataManager', 'team_id' => $teamId, 'role_ids' => [9,11], 'email' => 'CypressDarMetadataManager@hdruk.ac.uk'],
         ];

        foreach ($users as $data) {

            $user = User::create(
                [
                    'email' => $data['email'],
                    'name' => $data['name'],
                    'provider' => 'service',
                    'password' => Hash::make(env('TEST_USER_PASSWORD')),
                ]
            );
            $teamUser = TeamHasUser::create(
                [
                    'team_id' => $teamId,
                    'user_id' => $user->id,
                ]
            );
            foreach ($data['role_ids'] as $role_id) {
                TeamUserHasRole::firstOrCreate([
                    'team_has_user_id' => $teamUser->id,
                    'role_id' => $role_id,
                ]);
            }
        }
    }
}
