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
            $table->integer('team_id', null)->after('user_id')->default(-99);
            $table->integer('user_id', null)->default(-99)->change();
            $table->string('action_type', 50)->default('UNKNOWN')->after('team_id');
            $table->string('action_service', null)->nullable()->after('action_type');
            $table->text('description')->change();
            $table->dropColumn('function');
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
            $table->string('function', 128)->nullable();
        });
    }
};
