<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Dur;
use App\Models\User;
use App\Models\Publication;
use Illuminate\Database\Seeder;
use App\Models\DurHasPublication;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DurHasPublicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $userId = User::all()->random()->id;
            $publicationId = Publication::all()->random()->id;
            $durId = Dur::all()->random()->id;

            $durHasPublication = DurHasPublication::where([
                'dur_id' => $durId,
                'publication_id' => $publicationId,
            ])->first();

            if (!$durHasPublication) {
                DurHasPublication::create([
                    'dur_id' => $durId,
                    'publication_id' => $publicationId,
                    'user_id' => $userId,
                    'reason' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                    'is_locked' => fake()->randomElement([0, 1])
                ]);
            }
        }

        for ($i = 1; $i <= 10; $i++) {
            $applicationId = Application::all()->random()->id;
            $publicationId = Publication::all()->random()->id;
            $durId = Dur::all()->random()->id;

            $durHasPublication = DurHasPublication::where([
                'dur_id' => $durId,
                'publication_id' => $publicationId,
            ])->first();

            if (!$durHasPublication) {
                DurHasPublication::create([
                    'dur_id' => $durId,
                    'publication_id' => $publicationId,
                    'application_id' => $applicationId,
                    'reason' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                    'is_locked' => fake()->randomElement([0, 1])
                ]);
            }
        }
    }
}
