<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;  
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        // Create the new table with the updated name and column
        Schema::create('dataset_version_has_named_entities', function (Blueprint $table) {
            $table->bigInteger('dataset_version_id')->unsigned();
            $table->bigInteger('named_entities_id')->unsigned();

            $table->foreign('dataset_version_id')->references('id')->on('dataset_versions')->onDelete('cascade');
            $table->foreign('named_entities_id')->references('id')->on('named_entities')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        // Retrieve the correct dataset_version_id and insert data into the new table
        $oldData = DB::table('dataset_has_named_entities')->get();

        foreach ($oldData as $data) {
            $datasetVersion = DB::table('dataset_versions')->where('dataset_id', $data->dataset_id)->first();

            if ($datasetVersion) {
                DB::table('dataset_version_has_named_entities')->insert([
                    'dataset_version_id' => $datasetVersion->id,
                    'named_entities_id' => $data->named_entities_id,
                    'created_at' => $data->created_at,
                    'updated_at' => $data->updated_at,
                ]);
            }
        }

        
        // Drop the old table
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('dataset_has_named_entities');
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Create the old table with the original name and column
        Schema::create('dataset_has_named_entities', function (Blueprint $table) {
            $table->bigInteger('dataset_id')->unsigned();
            $table->bigInteger('named_entities_id')->unsigned();

            $table->foreign('dataset_id')->references('id')->on('datasets')->onDelete('cascade');
            $table->foreign('named_entities_id')->references('id')->on('named_entities')->onDelete('cascade');

            $table->timestamps();
        });

        // Retrieve the correct dataset_id and insert data back into the old table
        $newData = DB::table('dataset_version_has_named_entities')->get();

        foreach ($newData as $data) {
            $dataset = DB::table('dataset_versions')->where('id', $data->dataset_version_id)->first();

            if ($dataset) {
                DB::table('dataset_has_named_entities')->insert([
                    'dataset_id' => $dataset->dataset_id,
                    'named_entities_id' => $data->named_entities_id,
                    'created_at' => $data->created_at,
                    'updated_at' => $data->updated_at,
                ]);
            }
        }

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('dataset_version_has_named_entities');  
        Schema::enableForeignKeyConstraints();
    }
};
