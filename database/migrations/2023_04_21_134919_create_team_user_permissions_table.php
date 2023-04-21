<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('team_user_permissions', function (Blueprint $table) {
            $table->foreignId('team_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('permission_id')->constrained();
            $table->unique(['team_id', 'user_id', 'permission_id'], 'team_user_perms_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_user_permissions');
    }
};
