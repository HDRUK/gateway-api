<?php

namespace Database\Seeders;

use App\Models\QuestionBank;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuestionBankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        QuestionBank::factory(10)->create();
    }
}
