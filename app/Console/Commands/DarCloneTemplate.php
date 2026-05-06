<?php

namespace App\Console\Commands;

use App\Models\DataAccessTemplate;
use App\Models\DataAccessTemplateHasQuestion;
use App\Models\Team;
use Illuminate\Console\Command;

class DarCloneTemplate extends Command
{
    protected $signature = 'dar:clone-template
                            {--template-id=  : ID of the template to clone (required)}
                            {--team-id=      : ID of the origin team (required)}
                            {--to-team-id=   : ID of the destination team (required)}';

    protected $description = 'Clone a dar template from one team to another';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $templateId = $this->option('template-id');
        $fromTeamId = $this->option('team-id');
        $toTeamId   = $this->option('to-team-id');
 
        $missing = [];
        if (blank($templateId)) $missing[] = '--template-id';
        if (blank($fromTeamId)) $missing[] = '--team-id';
        if (blank($toTeamId))   $missing[] = '--to-team-id';
 
        if (!empty($missing)) {
            $this->error('The following options are required: ' . implode(', ', $missing));
            $this->line('');
            $this->line('Usage:');
            $this->line('  php artisan dar:clone-template --template-id=1 --team-id=2 --to-team-id=3');
            return self::FAILURE;
        }
 
        // 3. Validate all values are positive integers
        if (!ctype_digit((string) $templateId)) {
            $this->error('--template-id must be a positive integer.');
            return self::FAILURE;
        }
 
        if (!ctype_digit((string) $fromTeamId)) {
            $this->error('--team-id must be a positive integer.');
            return self::FAILURE;
        }
 
        if (!ctype_digit((string) $toTeamId)) {
            $this->error('--to-team-id must be a positive integer.');
            return self::FAILURE;
        }
 
        if ($fromTeamId === $toTeamId) {
            $this->error('Origin and destination teams must be different.');
            return self::FAILURE;
        }
 
        // 4. Check each record exists, collecting all errors before returning
        $notFound = [];
 
        if (!DataAccessTemplate::where('id', $templateId)->exists()) {
            $notFound[] = "--template-id: template with ID [{$templateId}] does not exist.";
        }
 
        $originTeam = Team::find($fromTeamId);
        if (!$originTeam) {
            $notFound[] = "--team-id: origin team with ID [{$fromTeamId}] does not exist.";
        }

        $destinationTeam = Team::find($toTeamId);
        if (!$destinationTeam) {
            $notFound[] = "--to-team-id: destination team with ID [{$toTeamId}] does not exist.";
        }
 
        if (!empty($notFound)) {
            foreach ($notFound as $message) {
                $this->error($message);
            }
            return self::FAILURE;
        }

        $originTemplate = DataAccessTemplate::where([
            'id'      => $templateId,
            'team_id' => $fromTeamId,
        ])->exists();
 
        if (!$originTemplate) {
            $this->error("Template [{$templateId}] does not belong to team [{$fromTeamId}].");
            return self::FAILURE;
        }

        $template = DataAccessTemplate::findOrFail($templateId);
        $templateQuestions = DataAccessTemplateHasQuestion::where('template_id', $templateId)->get();

        $cloned = \DB::transaction(function () use($template, $templateQuestions, $toTeamId) {
            $clonedTemplate = $template->replicate();
            $clonedTemplate->team_id = (int) $toTeamId;
            $clonedTemplate->user_id = $template->user_id;
            $clonedTemplate->published = false;
            $clonedTemplate->locked = false;
            $clonedTemplate->save();

            $clonedTemplateId = $clonedTemplate->id;

            foreach ($templateQuestions as $question) {
                $clonedQuestion = $question->replicate();
                $clonedQuestion->template_id = $clonedTemplateId;
                $clonedQuestion->question_id = $question->question_id;
                $clonedQuestion->guidance = $question->guidance;
                $clonedQuestion->required = $question->required;
                $clonedQuestion->order = $question->order;
                $clonedQuestion->question_title = $question->question_title;
                $clonedQuestion->save();
            }

            return $clonedTemplate;
        });

        $this->info("Template cloned successfully.");
        $this->line("New template ID : <comment>{$cloned->id}</comment>");
        $this->line("Destination team: <comment>{$toTeamId}</comment>");

        return self::SUCCESS;
    }
}
