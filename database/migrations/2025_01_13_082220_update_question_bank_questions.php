<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('question_bank_questions', function (Blueprint $table) {
            $table->string('question_type')->default('STANDARD');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_bank_questions', function (Blueprint $table) {
            $table->dropColumn('question_type');
        });
    }
};
