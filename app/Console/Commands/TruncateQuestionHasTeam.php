<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\QuestionHasTeam;

class TruncateQuestionHasTeam extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:truncate-question-has-team';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate question has teams table - updating handling of custom questions (GAT-5995)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        QuestionHasTeam::truncate();
    }
}
