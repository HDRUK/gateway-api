<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Collection;
use Illuminate\Database\Seeder;
use App\Models\CollectionHasUser;

class CollectionHasUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $collections = Collection::select(['id'])->get();

        foreach ($collections as $collection) {
            $userId = User::all()->random()->id;

            CollectionHasUser::create([
                'collection_id' => $collection->id,
                'user_id' => $userId,
                'role' => 'CREATOR',
            ]);
        }
    }
}
