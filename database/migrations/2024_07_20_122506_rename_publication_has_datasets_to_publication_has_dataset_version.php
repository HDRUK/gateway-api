<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the new table with the updated name and columns
        Schema::create('publication_has_dataset_version', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('publication_id')->unsigned();
            $table->bigInteger('dataset_version_id')->unsigned();
            $table->enum('link_type', ['ABOUT', 'USING', 'UNKNOWN'])->default('UNKNOWN');
            $table->timestamps(); // Adding timestamps
            $table->softDeletes(); // Adding soft deletes

            $table->foreign('publication_id')->references('id')->on('publications');
            $table->foreign('dataset_version_id')->references('id')->on('dataset_versions');

            // Add indexes
            $table->index('publication_id');
            $table->index('dataset_version_id');
        });

        // Retrieve the correct dataset_version_id and insert data into the new table
        $oldData = DB::table('publication_has_dataset')->get();

        foreach ($oldData as $data) {
            $datasetVersion = DB::table('dataset_versions')->where('dataset_id', $data->dataset_id)->latest('created_at')->first();

            if ($datasetVersion) {
                DB::table('publication_has_dataset_version')->insert([
                    'publication_id' => $data->publication_id,
                    'dataset_version_id' => $datasetVersion->id,
                    'link_type' => $data->link_type,
                    'created_at' => $data->created_at  ?? Carbon::now(), // Set to current timestamp if not set
                    'updated_at' => $data->updated_at  ?? Carbon::now(), // Set to current timestamp if not set
                    'deleted_at' => $data->deleted_at  ?? null, // This handles cases where the original row was soft deleted
                ]);
            }
        }

        // Drop the old table
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('publication_has_dataset');
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Create the old table with the original name and columns (without softDeletes)
        Schema::create('publication_has_dataset', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('publication_id')->unsigned();
            $table->bigInteger('dataset_id')->unsigned();
            $table->enum('link_type', ['ABOUT', 'USING', 'UNKNOWN'])->default('UNKNOWN');
            $table->timestamps(); // Adding timestamps

            $table->foreign('publication_id')->references('id')->on('publications');
            $table->foreign('dataset_id')->references('id')->on('datasets');

            // Add indexes
            $table->index('publication_id');
            $table->index('dataset_id');

        });

        // Retrieve the correct dataset_id and insert data back into the old table
        $newData = DB::table('publication_has_dataset_version')->get();

        foreach ($newData as $data) {
            $dataset = DB::table('dataset_versions')->where('id', $data->dataset_version_id)->first();

            if ($dataset) {
                // Check for duplicates before inserting
                $exists = DB::table('publication_has_dataset')
                            ->where('publication_id', $data->publication_id)
                            ->where('dataset_id', $dataset->dataset_id)
                            ->exists();

                if (!$exists) {
                    DB::table('publication_has_dataset')->insert([
                        'publication_id' => $data->publication_id,
                        'dataset_id' => $dataset->dataset_id,
                        'link_type' => $data->link_type
                    ]);
                }
            }
        }

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('publication_has_dataset_version');
        Schema::enableForeignKeyConstraints();
    }
};
