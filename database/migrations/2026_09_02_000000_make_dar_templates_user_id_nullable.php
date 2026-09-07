<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * Allows `dar_templates.user_id` to be nulled out when its owning user
     * is hard-deleted via the admin transfer-and-delete flow, without
     * requiring an explicit reassignment decision (same treatment as
     * `dur.user_id` and `project_grants.user_id`).
     */
    public function up(): void
    {
        Schema::table('dar_templates', function (Blueprint $table) {
            $table->bigInteger('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('dar_templates')->whereNull('user_id')->delete();

        Schema::table('dar_templates', function (Blueprint $table) {
            $table->bigInteger('user_id')->nullable(false)->change();
        });
    }
};
