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
            'app:data-providers-post-migration',
            //'app:add-data-provider-network', - what is this/?
            'app:post-run-update-full-name-role',
            'app:update-eu-licenses',
            'app:physical-sample-post-migration',
            'app:datasets-post-migration',
            'app:publication-type-post-migration',
            'app:dataset-publication-linkage-post-migration',
            'app:reindex-entities datasets '.$sleep,
            'app:reindex-entities tools '.$sleep,
            'app:reindex-entities publications '.$sleep,
            'app:reindex-entities durs '.$sleep,

        ];

        foreach ($commands as $command) {
            $this->call($command, [
                '--nthreads' => 30,
            ]);
        }

        return 0;
    }
