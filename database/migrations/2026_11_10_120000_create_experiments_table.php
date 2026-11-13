<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('experiments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('status', ['draft', 'active', 'paused', 'archived'])->default('draft');
            $table->json('definition');
            $table->json('targeting');
            $table->json('trigger');
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedInteger('responses_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiments');
    }
};
