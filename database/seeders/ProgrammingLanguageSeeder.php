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
            'Kotlin',
            'Markdown',
            'Markup',
            'Matlab',
            'Node.js',
            'PHP',
            'Python',
            'R',
            'React',
            'SQL',
            'Stata',
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
