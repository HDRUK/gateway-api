<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('collection_has_dataset_version', function (Blueprint $table) {
                $table->dropForeign(['collection_id']);
                $table->dropForeign(['dataset_version_id']);
                $table->dropForeign(['user_id']);
                $table->dropForeign(['application_id']);
            });
        }

        Schema::table('collection_has_dataset_version', function (Blueprint $table) {
            $table->dropIndex('collection_has_dataset_version_collection_id_index');
            $table->dropIndex('collection_has_dataset_version_dataset_version_id_index');
            $table->dropIndex('collection_has_dataset_version_user_id_index');
            $table->dropIndex('collection_has_dataset_version_application_id_index');
        });

        Schema::rename('collection_has_dataset_version', 'collection_has_dataset_version_backup');

        // Create the new table
        Schema::create('collection_has_dataset_version', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('collection_id')->unsigned();
            $table->bigInteger('dataset_version_id')->unsigned();
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->bigInteger('application_id')->unsigned()->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('collection_id')->references('id')->on('collections')->onDelete('cascade');
            $table->foreign('dataset_version_id')->references('id')->on('dataset_versions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('application_id')->references('id')->on('applications')->onDelete('cascade');

            // Add indexes
            $table->index('collection_id');
            $table->index('dataset_version_id');
            $table->index('user_id');
            $table->index('application_id');
            $table->index('deleted_at');
        });

        DB::table('collection_has_dataset_version')->insert(
            DB::table('collection_has_dataset_version_backup')
                ->select('collection_id', 'dataset_version_id', 'user_id', 'application_id', 'reason', 'created_at', 'updated_at', 'deleted_at')
                ->get()
                ->map(function ($row) {
                    return (array) $row;
                })
                ->toArray()
        );

        Schema::dropIfExists('collection_has_dataset_version_backup');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('collection_has_dataset_version', function (Blueprint $table) {
                $table->dropForeign(['collection_id']);
                $table->dropForeign(['dataset_version_id']);
                $table->dropForeign(['user_id']);
                $table->dropForeign(['application_id']);
            });
        }

        Schema::table('collection_has_dataset_version', function (Blueprint $table) {
            $table->dropIndex('collection_has_dataset_version_collection_id_index');
            $table->dropIndex('collection_has_dataset_version_dataset_version_id_index');
            $table->dropIndex('collection_has_dataset_version_user_id_index');
            $table->dropIndex('collection_has_dataset_version_application_id_index');
        });

        Schema::rename('collection_has_dataset_version', 'collection_has_dataset_version_backup');

        // Create the new table
        Schema::create('collection_has_dataset_version', function (Blueprint $table) {
            $table->bigInteger('collection_id')->unsigned();
            $table->bigInteger('dataset_version_id')->unsigned();
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->bigInteger('application_id')->unsigned()->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('collection_id')->references('id')->on('collections')->onDelete('cascade');
            $table->foreign('dataset_version_id')->references('id')->on('dataset_versions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('application_id')->references('id')->on('applications')->onDelete('cascade');

            // Add indexes
            $table->index('collection_id');
            $table->index('dataset_version_id');
            $table->index('user_id');
            $table->index('application_id');
            $table->index('deleted_at');
        });

        DB::table('collection_has_dataset_version')->insert(
            DB::table('collection_has_dataset_version_backup')
                ->select('collection_id', 'dataset_version_id', 'user_id', 'application_id', 'reason', 'created_at', 'updated_at', 'deleted_at')
                ->get()
                ->map(function ($row) {
                    return (array) $row;
                })
                ->toArray()
        );

        Schema::dropIfExists('collection_has_dataset_version_backup');
    }
};
