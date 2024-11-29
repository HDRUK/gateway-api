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
        Schema::table('question_bank_questions', function (Blueprint $table) {
            $table->dropIndex(['team_id']);
            $table->dropColumn('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_bank_questions', function (Blueprint $table) {
            $table->bigInteger('team_id')->unsigned();
            $table->index('team_id');
        });

        // replace existing data? There isn't really a way to do that so fill with null?
    }
};
