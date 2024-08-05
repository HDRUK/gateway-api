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
            $table->tinyInteger('force_required');
            $table->tinyInteger('allow_guidance_override');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_bank_questions', function (Blueprint $table) {
            $table->dropColumn('force_required');
            $table->dropColumn('allow_guidance_override');
        });
    }
};
