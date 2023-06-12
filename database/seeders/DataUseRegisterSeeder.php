<?php

namespace Database\Seeders;

use App\Models\DataUseRegister;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DataUseRegisterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DataUseRegister::factory()->count(10)->create();
    }
}
