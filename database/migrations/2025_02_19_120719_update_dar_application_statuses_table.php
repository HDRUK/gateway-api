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
        Schema::table('dar_application_statuses', function (Blueprint $table) {
            $table->bigInteger('review_id')->unsigned()->nullable();
            $table->bigInteger('team_id')->unsigned()->nullable();

            $table->foreign('review_id')->references('id')->on('dar_application_reviews');
            $table->foreign('team_id')->references('id')->on('teams');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dar_application_statuses', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropForeign(['review_id']);
            $table->dropColumn(['team_id', 'review_id']);
        });
    }
};
