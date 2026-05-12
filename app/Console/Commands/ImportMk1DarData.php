<?php

namespace App\Console\Commands;

use App\Models\DataAccessApplication;
use App\Models\DataAccessApplicationAnswer;
use App\Models\DataAccessApplicationHasDataset;
use App\Models\DataAccessApplicationHasQuestion;
use App\Models\DataAccessTemplate;
use App\Models\Dataset;
use App\Models\Team;
use App\Models\TeamHasDataAccessApplication;
use App\Models\User;
use Illuminate\Console\Command;

class ImportMk1DarData extends Command
{
    protected $signature = 'app:import-dar-mk1
                                {--user-id= : User ID (required)}
                                {--template-id= : Template ID (required)}
                                {--team-id= : Team ID (required)}
    ';

    protected $description = 'Import DAR data from MK1, scoped to a user, template, and team';

    private array $darQuestionsMapping = [];
    private array $skippedQuestionIds = [369, 388, 463, 488, 542, 662, 410];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // --- Validate
        $userId     = $this->option('user-id');
        $templateId = $this->option('template-id');
        $teamId     = $this->option('team-id');

        $missing = array_keys(array_filter([
            '--user-id'     => $userId,
            '--template-id' => $templateId,
            '--team-id'     => $teamId,
        ], fn ($v) => blank($v)));

        if (! empty($missing)) {
            foreach ($missing as $opt) {
                $this->error("{$opt} is required.");
            }
            return self::FAILURE;
        }

        if (! User::where('id', $userId)->exists()) {
            $this->error("User with ID {$userId} not found.");
            return self::FAILURE;
        }

        if (! DataAccessTemplate::where('id', $templateId)->exists()) {
            $this->error("DataAccessTemplate with ID {$templateId} not found.");
            return self::FAILURE;
        }

        if (! Team::where('id', $teamId)->exists()) {
            $this->error("Team with ID {$teamId} not found.");
            return self::FAILURE;
        }

        $this->info("Validated: User #{$userId}, Template #{$templateId}, Team #{$teamId}.");

        $this->readCsvFile(storage_path() . '/migration_files/dar_question_id_mapping.csv');
        $darMk1Data = $this->readJsonFile(storage_path() . '/migration_files/production.data_requests.json');

        foreach ($darMk1Data as $itemMk1) {
            if (!array_key_exists('questionAnswers', $itemMk1)) {
                $this->error("no answers found; skip record " . $itemMk1['_id']['$oid']);
                continue;
            }

            $projectTitle = 'DAR Import from MK1 ' . now()->format('Y-m-d H:i:s');
            if (array_key_exists('projectName', $itemMk1)) {
                $projectTitle = $itemMk1['projectName'];
            }

            if (!array_key_exists('datasetIds', $itemMk1)) {
                $this->error("no dataset related found " . $itemMk1['_id']['$oid']);
                continue;
            }

            if (!$this->checkingDatasetIds($itemMk1['datasetIds'])) {
                $this->error("no dataset related found " . $itemMk1['_id']['$oid']);
                continue;
            }

            $darApps = DataAccessApplication::create([
                'applicant_id' => $userId,
                'project_title' => $projectTitle,
                'application_type' => 'FORM',
                'project_id' => 0, // temporary placeholder
                'approval_status' => null,
                'submission_status' => 'DRAFT',
            ]);
            $darApps->update(['project_id' => $darApps->id]);

            TeamHasDataAccessApplication::create([
                'dar_application_id' => $darApps->id,
                'team_id' => $teamId,
            ]);

            $this->linkDatasetIds($itemMk1['datasetIds'], $darApps->id);

            $this->addQuestionsToApplications($teamId, $templateId, $darApps->id);

            $this->addAnswersToDarApp($itemMk1['questionAnswers'], $darApps->id, $userId);

            $this->info("added answers from record " . $itemMk1['_id']['$oid']);
        }

        $this->info('Import complete.');

        return self::SUCCESS;
    }

    private function addQuestionsToApplications(int $teamId, int $templateId, int $darAppId): void
    {
        $questions = [];
        $team = Team::where('id', $teamId)->first();

        $template = DataAccessTemplate::where([
            'id' => $templateId,
            'team_id' => $team->id,
        ])->first();
        if ($template) {
            $templateQuestions = $template->questions()->get();
            foreach ($templateQuestions as $q) {
                $q['team'] = $team->name;
                if (!isset($questions[$q->question_id])) {
                    $questions[$q->question_id] = [$q];
                } else {
                    $questions[$q->question_id][] = $q;
                }
            }
        }

        $order = 1;
        foreach ($questions as $qId => $question) {
            $required = in_array(true, $question) ? true : false;
            $teams = implode(',', array_column($question, 'team'));

            $guidanceArray = array();
            foreach ($question as $q) {
                if (isset($guidanceArray[$q['guidance']])) {
                    $guidanceArray[$q['guidance']][] = $q['team'];
                } else {
                    $guidanceArray[$q['guidance']] = [$q['team']];
                }
            }
            $guidance = '';
            foreach ($guidanceArray as $g => $t) {
                $guidance .= '<b>' . implode(',', $t) . '</b>' . '<p><em>' . $g . '</em><p/>';
            }

            DataAccessApplicationHasQuestion::create([
                'application_id' => $darAppId,
                'question_id' => $qId,
                'guidance' => $guidance,
                'required' => $required,
                'order' => $order,
                'teams' => $teams
            ]);
            $order += 1;
        }
    }

    private function addAnswersToDarApp(array $questionAnswers, int $darAppId, $userId): void
    {
        foreach ($questionAnswers as $key => $value) {
            $question = $this->getMK2QuestionId($key);

            if ($question === 'not_found') {
                continue;
            }

            if (in_array($value, $this->skippedQuestionIds)) {
                continue;
            }

            DataAccessApplicationAnswer::create([
                'question_id' => $question,
                'application_id' => $darAppId,
                'answer' => $value,
                'contributor_id' => $userId,
            ]);
        }
    }

    private function checkingDatasetIds(array $datasetIds): bool
    {
        $return = false;

        foreach ($datasetIds as $datasetId) {
            $dataset = Dataset::where('datasetid', $datasetId)->first();
            if (!is_null($dataset)) {
                $return = true;
            }
        }

        return $return;
    }

    private function linkDatasetIds(array $datasetIds, int $darAppId): void
    {
        foreach ($datasetIds as $datasetId) {
            $dataset = Dataset::where('datasetid', $datasetId)->first();
            if (!is_null($dataset)) {
                DataAccessApplicationHasDataset::create([
                    'dar_application_id' => $darAppId,
                    'dataset_id' => $dataset->id
                ]);
            }
        }
    }

    private function readCsvFile(string $migrationFile): void
    {
        $file = fopen($migrationFile, 'r');
        $headers = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            $item = [];
            foreach ($row as $key => $value) {
                $item[trim($headers[$key], "\xEF\xBB\xBF")] = $value ?: '';
            }

            $this->darQuestionsMapping[] = $item;
        }

        fclose($file);
    }

    private function readJsonFile(string $migrationFile): array
    {
        if (! file_exists($migrationFile)) {
            $this->error("JSON file not found: {$migrationFile}");
            return [];
        }

        $contents = file_get_contents($migrationFile);
        $decoded  = json_decode($contents, associative: true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Failed to parse JSON file: ' . json_last_error_msg());
            return [];
        }

        return $decoded;
    }

    private function getMK2QuestionId(string $questionId): string
    {
        $match = collect($this->darQuestionsMapping)->firstWhere('question id', $questionId);

        return $match ? $match['MK2 question_id'] : 'not_found';
    }
}
