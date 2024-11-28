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
        Schema::create('qb_question_has_team', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('team_id')->unsigned();
            $table->bigInteger('qb_question_id')->unsigned();

            $table->foreign('team_id')->references('id')->on('teams');
            $table->foreign('qb_question_id')->references('id')->on('question_bank_questions');

            $table->unique(['team_id', 'qb_question_id']);
            $table->index('team_id');
            $table->index('qb_question_id');
        });

        // Fill with existing data?
        // Nulls mean all teams have access to the question
        $questions = DB::select('select id, team_id from question_bank_questions');
        // ??? all teams or select where is_question_bank is true
        $qb_teams = DB::table('teams')->select('id')->pluck('id');

        foreach ($questions as $question) {
            if (is_null($question->team_id)) {
                foreach ($qb_teams as $team) {
                    DB::insert(
                        'insert into qb_question_has_team (team_id, qb_question_id) values (?, ?)',
                        [$team, $question->id]
                    );
                }
            } else {
                DB::insert(
                    'insert into qb_question_has_team (team_id, qb_question_id) values (?, ?)',
                    [$question->team_id, $question->id]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qb_question_has_team');
    }
};
