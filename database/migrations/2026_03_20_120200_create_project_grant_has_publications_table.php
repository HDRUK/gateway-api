<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_grant_has_publications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_grant_version_id');
            $table->unsignedBigInteger('publication_id');

            $table->foreign('project_grant_version_id')
                ->references('id')
                ->on('project_grant_versions')
                ->onDelete('cascade');

            $table->foreign('publication_id')
                ->references('id')
                ->on('publications')
                ->onDelete('cascade');

            $table->timestamps();

            $table->unique(
                ['project_grant_version_id', 'publication_id'],
                'project_grant_version_publication_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_grant_has_publications');
    }
};
