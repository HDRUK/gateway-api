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
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->integer('user_id')->default(-99)->change();
            $table->integer('team_id')->default(-99)->after('user_id');
            $table->string('action_type', 50)->default('UNKNOWN');
            $table->string('action_service')->nullable();
            $table->text('description')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('team_id');
            $table->dropColumn('action_type');
            $table->dropColumn('action_service');
        });
    }
};
