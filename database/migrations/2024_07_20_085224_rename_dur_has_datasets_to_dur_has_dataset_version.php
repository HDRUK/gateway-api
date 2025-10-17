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
        Schema::create('dur_has_dataset_version', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('dur_id')->unsigned();
            $table->bigInteger('dataset_version_id')->unsigned();
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->bigInteger('application_id')->unsigned()->nullable();
            $table->boolean('is_locked')->default(false);
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('dur_id')->references('id')->on('dur');
            $table->foreign('dataset_version_id')->references('id')->on('dataset_versions');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('application_id')->references('id')->on('applications');

            // Add indexes
            $table->index('dur_id');
            $table->index('dataset_version_id');
            $table->index('user_id');
            $table->index('application_id');
        });

        // Retrieve the correct dataset_version_id and insert data into the new table
        $oldData = DB::table('dur_has_datasets')->get();

        foreach ($oldData as $data) {
            $datasetVersion = DB::table('dataset_versions')->where('dataset_id', $data->dataset_id)->latest('created_at')->first();

            if ($datasetVersion) {
                DB::table('dur_has_dataset_version')->insert([
                    'dur_id' => $data->dur_id,
                    'dataset_version_id' => $datasetVersion->id,
                    'user_id' => $data->user_id,
                    'application_id' => $data->application_id,
                    'is_locked' => $data->is_locked,
                    'reason' => $data->reason,
                    'created_at' => $data->created_at,
                    'updated_at' => $data->updated_at,
                ]);
            }
        }

        // Drop the old table
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('dur_has_datasets');
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Create the old table with the original name and columns
        Schema::create('dur_has_datasets', function (Blueprint $table) {
            $table->bigInteger('dur_id')->unsigned();
            $table->bigInteger('dataset_id')->unsigned();
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->bigInteger('application_id')->unsigned()->nullable();
            $table->boolean('is_locked')->default(false);
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('dur_id')->references('id')->on('dur');
            $table->foreign('dataset_id')->references('id')->on('datasets');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('application_id')->references('id')->on('applications');

            // Add composite primary key
            $table->primary(['dur_id', 'dataset_id']);
        });

        // Retrieve the correct dataset_id and insert data back into the old table
        $newData = DB::table('dur_has_dataset_version')->get();

        foreach ($newData as $data) {
            $dataset = DB::table('dataset_versions')->where('id', $data->dataset_version_id)->first();

            if ($dataset) {
                // Check for duplicates before inserting
                $exists = DB::table('dur_has_datasets')
                            ->where('dur_id', $data->dur_id)
                            ->where('dataset_id', $dataset->dataset_id)
                            ->exists();

                if (!$exists) {
                    DB::table('dur_has_datasets')->insert([
                        'dur_id' => $data->dur_id,
                        'dataset_id' => $dataset->dataset_id,
                        'user_id' => $data->user_id,
                        'application_id' => $data->application_id,
                        'is_locked' => $data->is_locked,
                        'reason' => $data->reason,
                        'created_at' => $data->created_at,
                        'updated_at' => $data->updated_at,
                    ]);
                }
            }
        }

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('dur_has_dataset_version');
        Schema::enableForeignKeyConstraints();
    }
};
