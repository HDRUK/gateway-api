<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
<<<<<<< HEAD
<<<<<<< HEAD
=======
=======
>>>>>>> Migrated LinkedDataset to DatasetVersionHasDatasetVersion
<<<<<<< HEAD:database/migrations/2023_12_15_144311_create_dataset_versions_table.php
        Schema::create('dataset_versions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();

            $table->bigInteger('dataset_id')->unsigned();
            $table->json('metadata');
            $table->integer('version');
=======
<<<<<<< HEAD
>>>>>>> Migrated LinkedDataset to DatasetVersionHasDatasetVersion
=======
>>>>>>> Migrated LinkedDataset to DatasetVersionHasDatasetVersion
        Schema::create('dataset_version_has_tools', function (Blueprint $table) {
            $table->bigInteger('dataset_version_id')->unsigned();
            $table->bigInteger('tool_id')->unsigned();

            $table->primary(['dataset_version_id', 'tool_id']);
            $table->foreign('dataset_version_id')->references('id')->on('dataset_versions')->onDelete('cascade');
            $table->foreign('tool_id')->references('id')->on('tools')->onDelete('cascade');
<<<<<<< HEAD
<<<<<<< HEAD

            $table->timestamps();
=======
>>>>>>> Migrated LinkedDataset to DatasetVersionHasDatasetVersion:database/migrations/2024_03_07_104300_create_dataset_version_has_tool_table.php

            $table->foreign('dataset_id')->references('id')->on('datasets');
>>>>>>> Migrated LinkedDataset to DatasetVersionHasDatasetVersion
=======
>>>>>>> Migrated LinkedDataset to DatasetVersionHasDatasetVersion:database/migrations/2024_03_07_104300_create_dataset_version_has_tool_table.php

            $table->foreign('dataset_id')->references('id')->on('datasets');
>>>>>>> Migrated LinkedDataset to DatasetVersionHasDatasetVersion
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
<<<<<<< HEAD
<<<<<<< HEAD
        Schema::dropIfExists('dataset_version_has_tools');
=======
=======
>>>>>>> Migrated LinkedDataset to DatasetVersionHasDatasetVersion
<<<<<<< HEAD:database/migrations/2023_12_15_144311_create_dataset_versions_table.php
        Schema::dropIfExists('dataset_versions');
=======
        Schema::dropIfExists('dataset_version_has_tools');
>>>>>>> Migrated LinkedDataset to DatasetVersionHasDatasetVersion:database/migrations/2024_03_07_104300_create_dataset_version_has_tool_table.php
<<<<<<< HEAD
>>>>>>> Migrated LinkedDataset to DatasetVersionHasDatasetVersion
=======
>>>>>>> Migrated LinkedDataset to DatasetVersionHasDatasetVersion
    }
};
