<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('project_grant_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_grant_id');

            $table->unsignedInteger('version');

            $table->string('project_grant_name');
            $table->string('lead_researcher')->nullable();
            $table->string('lead_research_institute')->nullable();

            $table->json('grant_numbers')->nullable();

            $table->date('project_grant_start_date')->nullable();
            $table->date('project_grant_end_date')->nullable();
            $table->text('project_grant_scope')->nullable();

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
