<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TeamSeederAddPid extends Seeder
{
    /**
     * Run the database update
     */
    public function run(): void
    {
        Team::all()->each(function ($model) {
            $model->update(['pid' => (string) Str::uuid()]);
        });
    }
}
