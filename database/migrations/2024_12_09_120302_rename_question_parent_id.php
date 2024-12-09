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
        // This reduces confusion about "parent question" to a version, vs "parent-child" relationship between nested versions.
        Schema::table('question_bank_versions', function (Blueprint $table) {
            $table->dropIndex('question_bank_versions_question_parent_id_index');
            $table->renameColumn('question_parent_id', 'question_id');
            $table->index('question_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_bank_versions', function (Blueprint $table) {
            $table->dropIndex('question_bank_versions_question_id_index');
            $table->renameColumn('question_id', 'question_parent_id');
            $table->index('question_parent_id');
        });
    }
};
