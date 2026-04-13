<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Workgroup;
use Illuminate\Console\Command;

class ImportUserWorkgroupsFromCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-user-workgroups-from-csv {file?} {--sync : Actually sync the workgroup to the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reads a CSV of users/workgroups, finds users, cohort requests, and workgroups, and optionally syncs workgroups';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file') ?: storage_path('migration_files/user_workgroups.csv');

        if (! file_exists($filePath)) {
            $this->error("CSV file not found: {$filePath}");

            return self::FAILURE;
        }

        $csvData = $this->readMigrationFile($filePath);

        if (empty($csvData)) {
            $this->warn('No rows found in CSV.');

            return self::SUCCESS;
        }

        $progressBar = $this->output->createProgressBar(count($csvData));
        $progressBar->start();

        foreach ($csvData as $index => $row) {
            $userType = trim($row['user_type'] ?? '');
            $email = strtolower(trim($row['email'] ?? ''));
            $firstName = trim($row['firstname'] ?? '');
            $lastName = trim($row['lastname'] ?? '');
            $workgroupName = trim($row['workgroup'] ?? '');
            $workgroupType = trim($row['workgroup_type'] ?? '');

            if ($userType !== 'User') {
                $progressBar->advance();

                continue;
            }

            if ($email === '') {
                $this->newLine();
                $this->warn('Row '.($index + 1).': email is blank, skipping.');
                $progressBar->advance();

                continue;
            }

            $user = User::with(['cohortRequests', 'workgroups'])
                ->where('email', $email)
                ->first();

            if (! $user) {
                $user = User::with(['cohortRequests', 'workgroups'])
                    ->where('secondary_email', $email)
                    ->first();
            }

            if (! $user) {
                $this->newLine();
                $this->warn('Row '.($index + 1).": user not found for email {$email}");
                $progressBar->advance();

                continue;
            }

            $cohortRequests = $user->cohortRequests;
            $latestCohortRequest = $cohortRequests->sortByDesc('id')->first();

            $workgroup = null;
            if ($workgroupName !== '') {
                $normalizedWorkgroupName = str($workgroupName)->lower()->replace(' ', '-')->toString();

                $workgroup = Workgroup::where('name', $normalizedWorkgroupName)->first();
            }

            $this->newLine();
            $this->info('CSV Row '.($index + 1));
            $this->line("User: {$user->id} | {$user->email} | {$firstName} {$lastName}");
            $this->line("User type: {$userType}");
            $this->line("Workgroup from CSV: {$workgroupName}");
            $this->line("Workgroup type from CSV: {$workgroupType}");

            if ($latestCohortRequest) {
                $this->line(
                    "Latest CohortRequest: id={$latestCohortRequest->id}, status={$latestCohortRequest->request_status}"
                );
            } else {
                $this->line('Latest CohortRequest: none found');
            }

            if ($workgroup) {
                $this->line("Matched Workgroup: id={$workgroup->id}, name={$workgroup->name}");

                $alreadyAttached = $user->workgroups->contains('id', $workgroup->id);
                $this->line('Already attached to user: '.($alreadyAttached ? 'yes' : 'no'));

                if ($this->option('sync')) {
                    $user->workgroups()->syncWithoutDetaching([$workgroup->id]);

                    $this->info("Synced workgroup {$workgroup->id} to user {$user->id}");
                } else {
                    $this->comment('Dry run only. Use --sync to attach workgroup.');
                }
            } else {
                $this->warn("No matching workgroup found for name: {$workgroupName}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
        $this->info('Done.');

        return self::SUCCESS;
    }

    private function readMigrationFile(string $migrationFile): array
    {
        $response = [];

        $file = fopen($migrationFile, 'r');

        if ($file === false) {
            return [];
        }

        $headers = fgetcsv($file);

        if ($headers === false) {
            fclose($file);

            return [];
        }

        $normalizedHeaders = $this->normalizeHeaders($headers);

        while (($row = fgetcsv($file)) !== false) {
            $item = [];

            foreach ($normalizedHeaders as $key => $header) {
                $item[$header] = isset($row[$key]) ? trim($row[$key]) : '';
            }

            $response[] = $item;
        }

        fclose($file);

        return $response;
    }

    private function normalizeHeaders(array $headers): array
    {
        $seen = [];
        $normalized = [];

        foreach ($headers as $header) {
            $header = trim($header);

            // Normalize header names from CSV
            $mappedHeader = match ($header) {
                'Firstname' => 'firstname',
                'lastname' => 'lastname',
                'email' => 'email',
                'User type' => 'user_type',
                'workgroup' => 'workgroup',
                'workgroup type' => 'workgroup_type',
                default => str($header)->snake()->toString(),
            };

            // Handle duplicate email headers
            if (isset($seen[$mappedHeader])) {
                $seen[$mappedHeader]++;
                $mappedHeader = $mappedHeader.'_'.$seen[$mappedHeader];
            } else {
                $seen[$mappedHeader] = 1;
            }

            $normalized[] = $mappedHeader;
        }

        return $normalized;
    }
}
