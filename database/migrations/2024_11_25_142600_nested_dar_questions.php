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
            $table->boolean('is_child')->default(false);
        });
        Schema::create('question_bank_version_has_child_version', function (Blueprint $table) {
            $table->softDeletes();
            $table->bigInteger('parent_qbv_id')->unsigned();
            $table->bigInteger('child_qbv_id')->unsigned();
            $table->string('condition')->nullable();
            $table->foreign('parent_qbv_id')->references('id')->on('question_bank_versions')->onDelete('cascade');
            $table->foreign('child_qbv_id')->references('id')->on('question_bank_versions')->onDelete('cascade');
            $table->index('parent_qbv_id');
            $table->primary(['parent_qbv_id', 'child_qbv_id']);

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_bank_version_has_child_version', function (Blueprint $table) {
            $table->dropForeign(['parent_qbv_id']);
            $table->dropForeign(['child_qbv_id']);
        });

        Schema::table('question_bank_version_has_child_version', function (Blueprint $table) {
            $table->dropIndex('question_bank_version_has_child_version_parent_qbv_id_index');
        });

        Schema::dropIfExists('question_bank_version_has_child_version');

        Schema::table('question_bank_questions', function (Blueprint $table) {
            $table->dropColumn('is_child');
        });
    }
};
