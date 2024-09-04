<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'NLP System',
            'Data Visualisation',
            'infographic',
            'App (Docker/Kubernetes)',
            'Software',
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category,
                'enabled' => true,
            ]);
        }
    }
}
