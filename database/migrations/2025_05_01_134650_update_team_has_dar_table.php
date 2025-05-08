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
        Schema::table('team_has_dar_applications', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'submission_status', 'review_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_has_dar_applications', function (Blueprint $table) {
            $table->string('approval_status')->nullable();
            $table->string('submission_status')->default('DRAFT');
            $table->bigInteger('review_id')->nullable();
        });
    }
};
