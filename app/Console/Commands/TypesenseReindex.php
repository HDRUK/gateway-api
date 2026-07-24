<?php

namespace App\Console\Commands;

use App\SearchProviders\HDRUK;
use App\Services\TypesenseService;
use Illuminate\Console\Command;

class TypesenseReindex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'typesense:reindex
        {entity? : One of: datasets, tools, collections, dur, publications, data_custodian_networks, data_custodians}
        {--memory=1024M : memory_limit to apply for this command (bumped before the heavy import work runs)}
        {--list : List available entity types and exit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop, recreate, and re-import a search entity\'s Typesense collection';

    public function handle(TypesenseService $typesense): int
    {
        $modelMap = HDRUK::typesenseModelMap();

        if ($this->option('list')) {
            $this->info('Available entity types:');
            foreach ($modelMap as $entityKey => $modelClass) {
                $this->line("  {$entityKey} ({$modelClass})");
            }
            return self::SUCCESS;
        }

        $entity = $this->argument('entity');

        if ($entity === null) {
            $this->error('Missing required argument: entity. Run with --list to see available options.');
            return self::FAILURE;
        }

        if (!array_key_exists($entity, $modelMap)) {
            $this->error("Unknown entity '{$entity}'. Valid options: " . implode(', ', array_keys($modelMap)));
            return self::FAILURE;
        }

        ini_set('memory_limit', $this->option('memory'));

        $modelClass = $modelMap[$entity];
        $collectionName = (new $modelClass())->searchableAs();

        if ($typesense->collectionExists($collectionName)) {
            $this->info("Dropping existing collection '{$collectionName}'...");
            $typesense->dropCollection($collectionName);
        }

        $this->info("Creating collection '{$collectionName}' from {$modelClass}::typesenseCollectionSchema()...");
        $typesense->createCollectionFromModel($modelClass);

        $this->info("Importing {$modelClass} records...");
        $this->call('scout:import', ['model' => $modelClass]);

        $this->info("Done. '{$entity}' reindexed into '{$collectionName}'.");

        return self::SUCCESS;
    }
}
