<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('gwdm30_demographic_frequencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_version_id')
                ->constrained('dataset_versions')
                ->cascadeOnDelete();
            $table->enum('category', ['age', 'ethnicity', 'disease']);
            $table->string('bin', 100);
            $table->string('bin_vocabulary', 50)->nullable();
            $table->unsignedInteger('count');
            $table->timestamps();

            $table->index(['dataset_version_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gwdm30_demographic_frequencies');
    }
};
