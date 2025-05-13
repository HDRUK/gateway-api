<?php

namespace Database\Seeders;

use App\Models\Alias;
use Illuminate\Database\Seeder;

class AliasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            do {
                $alias = fake()->unique()->word();
            } while (strlen($alias) < 3);


            $checkAlias = Alias::where('name', $alias)->first();
            if (!is_null($checkAlias)) {
                continue;
            }

            Alias::create([
                'name' => $alias,
            ]);
        }
    }
}
