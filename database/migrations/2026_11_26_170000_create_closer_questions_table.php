<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The questions behind harvested CLOSER variables, kept to the minimum: the
 * short label and question text. Grid layout, response domains and routing
 * stay in CLOSER Discovery, linked from agency + identifier.
 *
 * Separate from closer_variables because a question is often shared (up to
 * ~500 variables for one grid in the ALSPAC pregnancy wave).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('closer_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('closer_wave_id')->constrained('closer_waves')->cascadeOnDelete();
            $table->string('identifier', 64);
            $table->unsignedInteger('version')->nullable();
            $table->string('type', 8)->nullable();
            $table->string('label')->nullable();
            $table->text('text')->nullable();
            $table->timestamps();

            $table->unique(['closer_wave_id', 'identifier']);
        });

        Schema::table('closer_variables', function (Blueprint $table) {
            $table->foreignId('closer_question_id')->nullable()->after('closer_dataset_id')
                ->constrained('closer_questions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('closer_variables', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closer_question_id');
        });

        Schema::dropIfExists('closer_questions');
    }
};
