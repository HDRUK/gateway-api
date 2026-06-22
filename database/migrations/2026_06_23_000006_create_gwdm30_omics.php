<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('gwdm30_omics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_version_id')
                ->constrained('dataset_versions')
                ->cascadeOnDelete();
            $table->string('assay', 100)->nullable();
            $table->string('platform', 100)->nullable();
            $table->unique('dataset_version_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gwdm30_omics');
    }
};
