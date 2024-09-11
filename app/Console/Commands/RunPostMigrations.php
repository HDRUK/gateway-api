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
    protected $signature = 'app:run-post-migrations {sleep=1} {--term-extraction}';

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
        $termExtraction = $this->option('term-extraction');

        $commands = [
            [
                'command' => 'app:add-super-admin-to-all-teams',
                'arguments' => [],
            ],
            [
                'command' => 'app:post-run-update-full-name-role',
                'arguments' => [
                    '--no-interaction' => true,
                ],
            ],
            // ['command' => 'app:sync-hubspot-contacts', 'arguments' => []], // What is this?
            //['command' => 'app:data-providers-post-migration', 'arguments' => []], // seed dataproviders

            [
                'command' => 'app:add-data-provider-network',
                'arguments' => [],
            ], // seed dataproviders
            [
                'command' => 'app:update-eu-licenses',
                'arguments' => [],
            ], // add licenses before post-migrating tools? - not working!!!!
            [
                'command' => 'app:tools-post-migration-process',
                'arguments' => [],
            ], // tools
            [
                'command' => 'app:publication-type-post-migration',
                'arguments' => [],
            ], // publications
            [
                'command' => 'app:dataset-publication-linkage-post-migration',
                'arguments' => [],
            ], // dataset linkage
            [
                'command' => 'app:upload-images-post-migration-process',
                'arguments' => [],
            ], // uploaded images
            [
                'command' => 'app:make-collections-active',
                'arguments' => [],
            ], // set collections to be active
            [
                'command' => 'app:dataset-linkages',
                'arguments' => [],
            ], // add dataset version linkages
            [
                'command' => 'app:team-dar-modal-content',
                'arguments' => [],
            ], // add team dar modal content
            [
                'command' => 'app:reindex-entities',
                'arguments' => [
                    'entity' => 'datasets',
                    'sleep' => $sleep,
                    '--term-extraction' => $termExtraction
                ],
            ],
            [
                'command' => 'app:reindex-entities',
                'arguments' => [
                    'entity' => 'tools',
                    'sleep' => $sleep,
                ],
            ],
            [
                'command' => 'app:reindex-entities',
                'arguments' => [
                    'entity' => 'publications',
                    'sleep' => $sleep,
                ],
            ],
            [
                'command' => 'app:reindex-entities',
                'arguments' => [
                    'entity' => 'durs',
                    'sleep' => $sleep,
                ],
            ],
            [
                'command' => 'app:reindex-entities',
                'arguments' => [
                    'entity' => 'collections',
                    'sleep' => $sleep,
                ],
            ],
            [
                'command' => 'app:reindex-entities',
                'arguments' => [
                    'entity' => 'dataProviders',
                    'sleep' => $sleep,
                ],
            ],
        ];

        foreach ($commands as $commandInfo) {
            $this->call($commandInfo['command'], $commandInfo['arguments']);
        }

        return 0;
    }
}
