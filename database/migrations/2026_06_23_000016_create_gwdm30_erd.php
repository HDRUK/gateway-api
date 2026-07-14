<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * gwdm30_erd — one row per dataset version.
 *
 * Only `description` is persisted in SQL. The `image` field is a binary
 * file upload — it stays in the JSON blob and is not replicated here.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('gwdm30_erd', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_version_id')
                ->constrained('dataset_versions')
                ->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->unique('dataset_version_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gwdm30_erd');
    }
};
