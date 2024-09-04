<?php

namespace Database\Seeders;

use App\Models\DataAccessApplicationAnswer;
use Illuminate\Database\Seeder;

class DataAccessApplicationAnswerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DataAccessApplicationAnswer::factory(1)->create();
    }
}
