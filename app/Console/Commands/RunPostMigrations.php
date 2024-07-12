<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunPostMigrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-post-migrations {sleep=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all post migrations in the correct order';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sleep = $this->argument("sleep");
        $commands = [
            'app:add-super-admin-to-all-teams' => [], //users
            'app:post-run-update-full-name-role' => ['--no-interaction' => true], //users
            // 'app:sync-hubspot-contacts' => [], // What is this?
            'app:data-providers-post-migration' => [], //seed dataproviders
            // 'app:add-data-provider-network' => [], // What is this?
            'app:physical-sample-post-migration' => [], //datasets
            'app:datasets-post-migration' => [], //datasets
            'app:update-eu-licenses' => [], //add licences before post-migrating tools?
            'app:tools-post-migration-process' => [], //tools
            'app:publication-type-post-migration' => [], //publications
            'app:dataset-publication-linkage-post-migration' => [], //dataset linkage
            'app:reindex-entities' => ['entity' => 'datasets', 'sleep' => $sleep],
            'app:reindex-entities' => ['entity' => 'tools', 'sleep' => $sleep],
            'app:reindex-entities' => ['entity' => 'publications', 'sleep' => $sleep],
            'app:reindex-entities' => ['entity' => 'durs', 'sleep' => $sleep],
            'app:reindex-entities' => ['entity' => 'collections', 'sleep' => $sleep],
            'app:reindex-entities' => ['entity' => 'dataProviders', 'sleep' => $sleep],
        ];

        foreach ($commands as $command => $arguments) {
            $this->call($command, $arguments);
        }
        return 0;
    }
}
