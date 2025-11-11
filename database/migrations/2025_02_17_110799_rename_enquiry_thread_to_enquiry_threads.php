<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enquiry_thread', function (Blueprint $table) {
            $table->dropIndex('enquiry_thread_team_id_index');
            $table->dropIndex('enquiry_thread_user_id_index');
        });

        Schema::rename('enquiry_thread', 'enquiry_threads');

        Schema::table('enquiry_threads', function (Blueprint $table) {
            $table->index('team_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enquiry_threads', function (Blueprint $table) {
            $table->dropIndex('enquiry_threads_team_id_index');
            $table->dropIndex('enquiry_threads_user_id_index');
        });

        Schema::rename('enquiry_threads', 'enquiry_thread');

        Schema::table('enquiry_thread', function (Blueprint $table) {
            $table->index('team_id');
            $table->index('user_id');
        });
    }
};
