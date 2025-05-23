<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the new table with the updated name and columns
        Schema::create('enquiry_thread_has_dataset_version', function (Blueprint $table) {
            $table->bigInteger('enquiry_thread_id')->unsigned();
            $table->bigInteger('dataset_version_id')->unsigned();
            $table->enum('interest_type', ['PRIMARY', 'SECONDARY']); // Determines primary Dataset interest or secondary
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('dataset_version_id')->references('id')->on('dataset_versions');

            // Add composite primary key
            $table->primary(['enquiry_thread_id', 'dataset_version_id']);
        });

        // Retrieve the correct dataset_version_id and insert data into the new table
        $oldData = DB::table('enquiry_thread_has_datasets')->get();

        foreach ($oldData as $data) {
            $datasetVersion = DB::table('dataset_versions')->where('dataset_id', $data->dataset_id)->latest('created_at')->first();

            if ($datasetVersion) {
                DB::table('enquiry_thread_has_dataset_version')->insert([
                    'enquiry_thread_id' => $data->enquiry_thread_id,
                    'dataset_version_id' => $datasetVersion->id,
                    'interest_type' => $data->interest_type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Drop the old table
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('enquiry_thread_has_datasets');
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Create the old table with the original name and columns
        Schema::create('enquiry_thread_has_datasets', function (Blueprint $table) {
            $table->bigInteger('enquiry_thread_id')->unsigned();
            $table->bigInteger('dataset_id')->unsigned();
            $table->enum('interest_type', ['PRIMARY', 'SECONDARY']); // Determines primary Dataset interest or secondary
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('dataset_id')->references('id')->on('datasets');

            $table->primary(['enquiry_thread_id', 'dataset_id']);
        });

        // Retrieve the correct dataset_id and insert data back into the old table
        $newData = DB::table('enquiry_thread_has_dataset_version')->get();

        foreach ($newData as $data) {
            $dataset = DB::table('dataset_versions')->where('id', $data->dataset_version_id)->first();

            if ($dataset) {
                DB::table('enquiry_thread_has_datasets')->insert([
                    'enquiry_thread_id' => $data->enquiry_thread_id,
                    'dataset_id' => $dataset->dataset_id,
                    'interest_type' => $data->interest_type,
                    'created_at' => $data->created_at,
                    'updated_at' => $data->updated_at,
                ]);
            }
        }

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('enquiry_thread_has_dataset_version');
        Schema::enableForeignKeyConstraints();
    }
};
