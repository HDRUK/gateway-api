<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CancerTypeFilter;
use Illuminate\Support\Facades\DB;

class CancerTypeFilterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Usage:
     * 1. Place your JSON file at storage/app/cancer_type_filters.json
     * 2. Or modify getFiltersData() method to include your JSON directly
     * 3. Run: php artisan db:seed --class=CancerTypeFilterSeeder
     */
    public function run(): void
    {
        // Clear existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('cancer_type_filters')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Load filters data
        $filtersData = $this->getFiltersData();
        
        if (empty($filtersData)) {
            $this->command->warn('No filters data found. Please provide JSON data.');
            return;
        }

        // Seed the data
        $this->seedFilters($filtersData, null, 0);
        
        $this->command->info('Cancer type filters seeded successfully!');
    }

    /**
     * Get filters data from JSON file or return empty array
     */
    private function getFiltersData(): array
    {
        // Try to load from JSON file first
        $jsonPath = storage_path('tests/Unit/test_files/cancer_type_filters.json');
        
        if (file_exists($jsonPath)) {
            $jsonContent = file_get_contents($jsonPath);
            $data = json_decode($jsonContent, true);
            
            if (json_last_error() === JSON_ERROR_NONE && !empty($data)) {
                return $data;
            }
        }
    }

    /**
     * Recursively seed filters from nested structure
     */
    private function seedFilters(array $filters, ?int $parentId, int $level, int &$sortOrder = 0): void
    {
        foreach ($filters as $key => $filter) {
            // Skip if not a valid filter structure
            if (!is_array($filter) || !isset($filter['id'])) {
                continue;
            }

            $sortOrder++;
            
            try {
                $cancerTypeFilter = CancerTypeFilter::create([
                    'filter_id' => (string)$filter['id'],
                    'label' => $filter['label'] ?? '',
                    'category' => $filter['category'] ?? null,
                    'primary_group' => $filter['primaryGroup'] ?? $filter['primary_group'] ?? null,
                    'count' => (string)($filter['count'] ?? '0'),
                    'parent_id' => $parentId,
                    'level' => $level,
                    'sort_order' => $sortOrder,
                ]);

                // Recursively process children
                if (isset($filter['children']) && is_array($filter['children']) && !empty($filter['children'])) {
                    $this->seedFilters($filter['children'], $cancerTypeFilter->id, $level + 1, $sortOrder);
                }
            } catch (\Exception $e) {
                $this->command->error("Error seeding filter {$filter['id']}: " . $e->getMessage());
                continue;
            }
        }
    }
}
