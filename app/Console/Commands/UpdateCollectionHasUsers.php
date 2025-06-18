<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Collection;
use App\Models\CollectionHasUser;
use Illuminate\Console\Command;

class UpdateCollectionHasUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-collection-has-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command update collections has users';

    private $csvData = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->readMigrationFile(storage_path() . '/migration_files/production.collections.csv');
        foreach ($this->csvData as $item) {
            $collectionMongoId = strtoupper(trim($item['_id']));
            $userAdmin = User::where('is_admin', 1)->first();

            $author0 = trim($item['authors[0]']);
            $author1 = trim($item['authors[1]']);
            $author2 = trim($item['authors[2]']);
            $author3 = trim($item['authors[3]']);
            $author4 = trim($item['authors[4]']);
            $author5 = trim($item['authors[5]']);
            $author6 = trim($item['authors[6]']);
            $author7 = trim($item['authors[7]']);
            $author8 = trim($item['authors[8]']);
            $author9 = trim($item['authors[9]']);
            $author10 = trim($item['authors[10]']);

            $authors = [];

            if (!empty($author0)) {
                $user = User::where('mongo_id', $author0)->first();
                if (!is_null($user)) {
                    $authors[] = $user->id;
                }
            }

            if (!empty($author1)) {
                $user = User::where('mongo_id', $author1)->first();
                if (!is_null($user)) {
                    $authors[] = $user->id;
                }
            }

            if (!empty($author2)) {
                $user = User::where('mongo_id', $author2)->first();
                if (!is_null($user)) {
                    $users[] = $user->id;
                }
            }

            if (!empty($author3)) {
                $user = User::where('mongo_id', $author3)->first();
                if (!is_null($user)) {
                    $authors[] = $user->id;
                }
            }

            if (!empty($author4)) {
                $user = User::where('mongo_id', $author4)->first();
                if (!is_null($user)) {
                    $authors[] = $user->id;
                }
            }

            if (!empty($author5)) {
                $user = User::where('mongo_id', $author5)->first();
                if (!is_null($user)) {
                    $authors[] = $user->id;
                }
            }

            if (!empty($author6)) {
                $user = User::where('mongo_id', $author6)->first();
                if (!is_null($user)) {
                    $authors[] = $user->id;
                }
            }

            if (!empty($author7)) {
                $user = User::where('mongo_id', $author7)->first();
                if (!is_null($user)) {
                    $authors[] = $user->id;
                }
            }

            if (!empty($author8)) {
                $user = User::where('mongo_id', $author8)->first();
                if (!is_null($user)) {
                    $authors[] = $user->id;
                }
            }

            if (!empty($author9)) {
                $user = User::where('mongo_id', $author9)->first();
                if (!is_null($user)) {
                    $authors[] = $user->id;
                }
            }

            if (!empty($author10)) {
                $user = User::where('mongo_id', $author10)->first();
                if (!is_null($user)) {
                    $authors[] = $user->id;
                }
            }

            $collection = Collection::where('mongo_object_id', $collectionMongoId)->first();

            if (!is_null($collection)) {
                CollectionHasUser::where('collection_id', $collection->id)->delete();

                // admin
                CollectionHasUser::create([
                    'collection_id' => $collection->id,
                    'user_id' => $userAdmin->id,
                    'role' => 'CREATOR',
                ]);

                // collections.authors
                foreach ($authors as $author) {
                    CollectionHasUser::create([
                        'collection_id' => $collection->id,
                        'user_id' => $author,
                        'role' => 'COLLABORATOR',
                    ]);
                }
            }
        }
    }

    private function readMigrationFile(string $migrationFile): void
    {
        $file = fopen($migrationFile, 'r');
        $headers = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            $item = [];
            foreach ($row as $key => $value) {
                $item[trim($headers[$key], "\xEF\xBB\xBF")] = $value ?: '';
            }

            $this->csvData[] = $item;
        }

        fclose($file);
    }
}
