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
        Schema::rename('enquiry_thread_has_datasets', 'enquiry_thread_has_dataset_version');

        Schema::table('enquiry_thread_has_dataset_version', function (Blueprint $table) {
            $table->renameColumn('dataset_id', 'dataset_version_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enquiry_thread_has_dataset_version', function (Blueprint $table) {
            $table->renameColumn('dataset_version_id', 'dataset_id');
        });

        Schema::rename('enquiry_thread_has_dataset_version', 'enquiry_thread_has_datasets');
    }
};
