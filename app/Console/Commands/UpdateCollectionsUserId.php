<?php

namespace App\Console\Commands;

use App\Models\Collection;
use App\Models\CollectionHasUser;
use App\Models\User;
use Illuminate\Console\Command;

class UpdateCollectionsUserId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-collections-user-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $admiUser = User::where('is_admin', 1)->first();

        if (!is_null($admiUser)) {
            $collections = Collection::select(['id'])->get()->toArray();

            foreach ($collections as $collection) {
                $collectionHasUsers = CollectionHasUser::where(['collection_id' => $collection['id']])->first();

                if (is_null($collectionHasUsers)) {
                    CollectionHasUser::create([
                        'collection_id' => $collection['id'],
                        'user_id' => $admiUser->id,
                        'role' => 'CREATOR'
                    ]);
                }
            }
        }
    }
}
