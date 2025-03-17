<?php

namespace Database\Seeders;

use App\Models\ProgrammingLanguage;
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
            'Java',
            'Javascript',
            'Markdown',
            'Markup',
            'PHP',
            'Python',
            'R',
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
