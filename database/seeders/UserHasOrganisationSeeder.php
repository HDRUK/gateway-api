<?php

namespace Database\Seeders;

use App\Models\Organisation;
use App\Models\User;
use App\Models\UserHasOrganisation;
use Illuminate\Database\Seeder;

class UserHasOrganisationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        $dataToInsert = [];
        foreach ($users as $user) {
            $dataToInsert[] = [
                'organisation_id' => Organisation::all()->random()->id,
                'user_id' => $user->id,
            ];
        }

        UserHasOrganisation::insert($dataToInsert);
    }
}