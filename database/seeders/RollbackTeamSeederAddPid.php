<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class RollbackTeamSeederAddPid extends Seeder
{
    /**
     * Run the database update
     */
    public function run(): void
    {
        Team::all()->each(function ($model) {
            $model->update(['pid' => null]);
        });
    }
}
