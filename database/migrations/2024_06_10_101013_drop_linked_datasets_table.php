<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('linked_datasets');
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('linked_datasets', function (Blueprint $table) {
            $table->bigIncrements('id'); // Optional if you want a primary key

            $table->unsignedBigInteger('dataset_1_id');
            $table->unsignedBigInteger('dataset_2_id');
            $table->string('linkage_type');
            $table->boolean('direct_linkage');
            $table->text('description')->nullable();

            // Unique key for combination of dataset_1_id, dataset_2_id, and linkage_type
            $table->unique(['dataset_1_id', 'dataset_2_id', 'linkage_type'], 'linkage_unique');

            // Indexes for foreign keys
            $table->index('dataset_1_id', 'dataset_1_fk_idx');
            $table->index('dataset_2_id', 'dataset_2_fk_idx');

            // Foreign key constraints
            $table->foreign('dataset_1_id', 'ld_dataset_1_id_fk')
                  ->references('id')->on('datasets')
                  ->onDelete('cascade');

            $table->foreign('dataset_2_id', 'ld_dataset_2_id_fk')
                  ->references('id')->on('datasets')
                  ->onDelete('cascade');
        });
    }
};
