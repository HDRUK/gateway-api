<?php

namespace App\Console\Commands;

use App\Models\CohortRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Console\Command;

class CohortPreprodUsersSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cohort-preprod-users-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save a list of users with cohort discovery access on preprod to a file.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $cohortRequests = CohortRequest::with(['user', 'logs', 'permissions'])->get();
        Storage::disk('local')->put('cohort_preprod_users.json', $cohortRequests);
    }
}
