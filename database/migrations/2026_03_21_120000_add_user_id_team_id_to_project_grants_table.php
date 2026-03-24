<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('project_grants')) {
            return;
        }

        Schema::table('project_grants', function (Blueprint $table) {
            if (!Schema::hasColumn('project_grants', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->index()->after('pid');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('project_grants', 'team_id')) {
                $table->unsignedBigInteger('team_id')->nullable()->index()->after('user_id');
                $table->foreign('team_id')->references('id')->on('teams')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        // Intentionally no-op.
        // The baseline create_project_grants migration now contains user_id/team_id,
        // so rolling this migration back must not remove those baseline columns.
        return;
    }
};
