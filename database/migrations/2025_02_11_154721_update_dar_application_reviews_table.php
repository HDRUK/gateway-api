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
        Schema::table('dar_application_reviews', function (Blueprint $table) {
            $table->dropColumn('review_comment');
        });

        Schema::table('dar_application_reviews', function (Blueprint $table) {
            $table->tinyInteger('resolved')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dar_application_reviews', function (Blueprint $table) {
            $table->text('review_comment');
        });

        Schema::table('dar_application_reviews', function (Blueprint $table) {
            $table->dropColumn('resolved');
        });
    }
};
