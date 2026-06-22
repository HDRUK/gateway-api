<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('gwdm30_linkage_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_version_id')
                ->constrained('dataset_versions')
                ->cascadeOnDelete();
            $table->text('is_generated_using')->nullable();
            $table->text('associated_media')->nullable();
            $table->text('data_uses')->nullable();
            $table->text('is_reference_in')->nullable();
            $table->text('tools')->nullable();
            $table->text('investigations')->nullable();
            $table->text('synthetic_data_web_link')->nullable();
            $table->unique('dataset_version_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gwdm30_linkage_meta');
    }
};
