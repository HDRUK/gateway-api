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
        Schema::rename('collection_has_dataset', 'collection_has_dataset_version');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('collection_has_dataset_version', 'collection_has_dataset');
    }
};
