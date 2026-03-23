<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_grants', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('version');
            $table->string('pid')->index();

            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('team_id')->nullable()->index();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('set null');

            $table->string('projectGrantName');
            $table->string('leadResearcher')->nullable();
            $table->string('leadResearchInstitute')->nullable();

            // JSON array (metadata provides `grantNumbers` as a string in many cases)
            $table->json('grantNumbers')->nullable();

            $table->date('projectGrantStartDate')->nullable();
            $table->date('projectGrantEndDate')->nullable();
            $table->text('projectGrantScope')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['pid', 'version', 'projectGrantName'], 'project_grants_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_grants');
    }
};

