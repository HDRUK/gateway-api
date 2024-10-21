<?php

use App\Models\Collection;
use App\Models\CollectionHasUser;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        $foreignKeys = $this->listTableForeignKeys('collections');
        if(in_array('collections_user_id_foreign', $foreignKeys)) {
            DB::statement('ALTER TABLE `collections` DROP FOREIGN KEY `collections_user_id_foreign`');
        }

        if (Schema::hasColumn('collections', 'user_id')) {
            $collections = Collection::select(['id', 'user_id'])->get();

            foreach ($collections as $collection) {
                if (!is_null($collection->user_id)) {
                    CollectionHasUser::create([
                        'collection_id' => $collection->id,
                        'user_id' => $collection->user_id,
                        'role' => 'CREATOR',
                    ]);
                }
            }

            Schema::table('collections', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        if (Schema::hasTable('collection_has_users')) {
            if (!Schema::hasColumn('collections', 'user_id')) {
                Schema::table('collections', function (Blueprint $table) {
                    $table->bigInteger('user_id')->nullable()->default(null)->unsigned();
                    $table->foreign('user_id')->references('id')->on('users');
                });
            }

            $collections = CollectionHasUser::select(['collection_id', 'user_id', 'CREATOR'])->get();
            foreach ($collections as $collection) {
                Collection::where([
                    'id' => $collection->collection_id,
                ])->update([
                    'user_id' => $collection->user_id,
                ]);
            }

            Schema::dropIfExists('collection_has_users');
        }
        Schema::enableForeignKeyConstraints();
    }

    public function listTableForeignKeys($table)
    {
        $conn = Schema::getConnection()->getDoctrineSchemaManager();

        return array_map(function ($key) {
            return $key->getName();
        }, $conn->listTableForeignKeys($table));
    }
};
