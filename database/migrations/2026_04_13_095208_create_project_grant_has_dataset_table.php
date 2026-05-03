<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('project_grant_has_dataset', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_grant_id');
            $table->unsignedBigInteger('dataset_id');

            $table->foreign('project_grant_id')
                ->references('id')
                ->on('project_grants')
                ->onDelete('cascade');

            $table->foreign('dataset_id')
                ->references('id')
                ->on('datasets')
                ->onDelete('cascade');

            $table->timestamps();

            $table->unique(['project_grant_id', 'dataset_id'], 'project_grant_dataset_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_grant_has_dataset');
    }
};
