<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_grant_has_tools', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_grant_version_id');
            $table->unsignedBigInteger('tool_id');

            $table->foreign('project_grant_version_id')
                ->references('id')
                ->on('project_grant_versions')
                ->onDelete('cascade');

            $table->foreign('tool_id')
                ->references('id')
                ->on('tools')
                ->onDelete('cascade');

            $table->timestamps();

            $table->unique(
                ['project_grant_version_id', 'tool_id'],
                'project_grant_version_tool_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_grant_has_tools');
    }
};
