<?php

namespace Database\Demo;

use Exception;

use App\Models\Filter;

use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FilterDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filters = include getcwd() . '/database/demo/files/filters_short.php';

        foreach ($filters as $key => $filter) {
            foreach ($filter as $item) {
                try {
                    $payload = [
                        'type' => $key,
                        'value' => $item,
                        'keys' => $key,
                        'enabled' => 1,
                    ];

                    Filter::create($payload);
                } catch (Exception $exception) {
                    throw new Exception($exception->getMessage());
                }
            }
        }
    }
}
