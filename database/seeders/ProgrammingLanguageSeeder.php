<?php

namespace Database\Seeders;

use App\Models\ProgrammingLanguage;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProgrammingLanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = [
            'C',
            'C++',
            'Python',
            'PHP',
            'Java',
            'Markup',
            'Javascript',
            'R',
            'Markdown',
            'SQL',
            'Visual Basic',
            'Other',
            'Not applicable',
        ];

        foreach ($names as $name) {
            ProgrammingLanguage::create([
                'name' => $name,
                'enabled' => true,       
            ]);
        }
    }
}
