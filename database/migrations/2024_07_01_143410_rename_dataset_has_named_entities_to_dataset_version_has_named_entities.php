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
        Schema::rename('dataset_has_named_entities', 'dataset_version_has_named_entities');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('dataset_version_has_named_entities', 'dataset_has_named_entities');
    }
};