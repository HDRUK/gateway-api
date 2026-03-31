<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_grant_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_grant_id');

            $table->unsignedInteger('version');

            $table->string('projectGrantName');
            $table->string('leadResearcher')->nullable();
            $table->string('leadResearchInstitute')->nullable();

            $table->json('grantNumbers')->nullable();

            $table->date('projectGrantStartDate')->nullable();
            $table->date('projectGrantEndDate')->nullable();
            $table->text('projectGrantScope')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('project_grant_id')
                ->references('id')
                ->on('project_grants')
                ->onDelete('cascade');

            $table->unique(['project_grant_id', 'version'], 'project_grant_versions_grant_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_grant_versions');
    }
};
