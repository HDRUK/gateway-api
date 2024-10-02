<?php

namespace Database\Seeders;

use App\Http\Enums\ActivityLogUserType as ActivityLogUserTypeEnums;
use App\Models\ActivityLogUserType;
use Illuminate\Database\Seeder;

class ActivityLogUserTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ActivityLogUserType::create([
            'name' => ActivityLogUserTypeEnums::ADMIN,
        ]);

        ActivityLogUserType::create([
            'name' => ActivityLogUserTypeEnums::CUSTODIAN,
        ]);

        ActivityLogUserType::create([
            'name' => ActivityLogUserTypeEnums::APPLICANT,
        ]);
    }
}
