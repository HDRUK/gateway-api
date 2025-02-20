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
            $table->string('submission_status')->nullable()->default('DRAFT');
            $table->string('approval_status')->nullable();
            $table->bigInteger('review_id')->unsigned()->nullable();
            $table->foreign('review_id')->references('id')->on('dar_application_reviews');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_has_dar_applications', function (Blueprint $table) {
            $table->dropIfExists('submission_status');
            $table->dropIfExists('approval_status');
            $table->dropIfExists('review_id');
        });
    }
};
