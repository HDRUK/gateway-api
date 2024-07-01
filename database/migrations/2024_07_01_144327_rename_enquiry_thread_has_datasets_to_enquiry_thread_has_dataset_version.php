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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('enquiry_thread_has_dataset_version', 'enquiry_thread_has_datasets');
    }
};
