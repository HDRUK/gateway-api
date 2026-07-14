<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * gwdm30_dataset_filters — one row per DatasetFilter entry.
 *
 * primaryGroup is constrained in the GWDM schema to the enum
 * cancer-type | data-type | access-type, stored as varchar here
 * so the DB layer stays decoupled from application enum evolution.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('gwdm30_dataset_filters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_version_id')
                ->constrained('dataset_versions')
                ->cascadeOnDelete();
            $table->string('filter_id')->nullable();
            $table->string('label', 150)->nullable();
            $table->string('category', 150)->nullable();
            $table->string('primary_group', 50)->nullable();
            $table->string('description', 150)->nullable();
            $table->timestamps();

            $table->index('dataset_version_id');
            $table->index('primary_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gwdm30_dataset_filters');
    }
};
