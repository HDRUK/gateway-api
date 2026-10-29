<?php

namespace App\Console\Commands;

use App\SearchProviders\HDRUK;
use App\Services\Search\VariableIndex;
use App\Services\TypesenseService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\ProgressBar;

class TypesenseReindex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'typesense:reindex
        {entity? : One of: datasets, tools, collections, dur, publications, data_custodian_networks, data_custodians, variables}
        {--memory=1024M : memory_limit to apply for this command (bumped before the heavy import work runs)}
        {--list : List available entity types and exit}
        {--resume : variables only: keep the collection and skip datasets already fully indexed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop, recreate, and re-import a search entity\'s Typesense collection';

    public function handle(TypesenseService $typesense, VariableIndex $variables): int
    {
        $modelMap = HDRUK::typesenseModelMap();

        if ($this->option('list')) {
            $this->info('Available entity types:');
            foreach ($modelMap as $entityKey => $modelClass) {
                $this->line("  {$entityKey} ({$modelClass})");
            }
            $this->line('  variables (' . VariableIndex::class . ')');
            return self::SUCCESS;
        }

        $entity = $this->argument('entity');

        if ($entity === null) {
            $this->error('Missing required argument: entity. Run with --list to see available options.');
            return self::FAILURE;
        }

        // Variables aren't a Scout model: one document per structural
        // metadata column, built by VariableIndex.
        if ($entity === 'variables') {
            ini_set('memory_limit', $this->option('memory'));
            return $this->rebuildVariables($variables);
        }

        if ($this->option('resume')) {
            $this->error('--resume only applies to variables.');
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

    /**
     * Rebuilds the variable index with a progress bar: each indexed or
     * failed dataset is printed above it, and the bar shows the current
     * dataset's import as it goes. -v also lists skipped datasets.
     */
    private function rebuildVariables(VariableIndex $variables): int
    {
        $collection = VariableIndex::collectionName();
        $resume = (bool) $this->option('resume');
        $started = microtime(true);
        /** @var ProgressBar|null $bar Created by the 'started' event. */
        $bar = null;
        $datasetStarted = 0.0;
        $tally = ['indexed' => 0, 'variables' => 0, 'failed' => 0, 'skipped' => []];

        // Prints above the bar rather than through it.
        $above = function (string $line, string $style = 'line') use (&$bar) {
            $bar?->clear();
            $this->{$style}($line);
            $bar?->display();
        };

        $this->info(($resume ? 'Resuming' : 'Rebuilding') . " '{$collection}'.");
        $this->line(sprintf(
            '  %d variables per request, %ds timeout per request.',
            config('typesense.variables.import_batch_size'),
            config('typesense.variables.import_timeout'),
        ));

        $total = $variables->rebuild(function (string $event, array $context) use (&$bar, &$datasetStarted, &$tally, $above) {
            switch ($event) {
                case 'dropping':
                    $this->line('  Dropping the existing collection.');
                    break;
                case 'creating':
                    $this->line('  Creating the collection (the first build downloads the embedding model)...');
                    break;
                case 'resuming':
                    $this->line("  Keeping the collection: {$context['indexed']} datasets already have variables indexed.");
                    break;
                case 'started':
                    $this->line("  Checking {$context['datasets']} dataset versions for variables.");
                    $bar = $this->output->createProgressBar($context['datasets']);
                    $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %message%');
                    $bar->setMessage('');
                    $bar->start();
                    break;
                case 'dataset':
                    $datasetStarted = microtime(true);
                    $bar->setMessage("dataset {$context['dataset_id']}: 0/" . number_format($context['variables']) . ' variables');
                    $bar->display();
                    break;
                case 'batch':
                    $elapsed = max(microtime(true) - $datasetStarted, 0.001);
                    $bar->setMessage(sprintf(
                        'dataset %d: %s/%s variables (%d/s)',
                        $context['dataset_id'],
                        number_format($context['done']),
                        number_format($context['variables']),
                        $context['done'] / $elapsed,
                    ));
                    $bar->display();
                    break;
                case 'indexed':
                    $tally['indexed']++;
                    $tally['variables'] += $context['variables'];
                    $above(sprintf(
                        '  ✓ dataset %d: %s variables in %s%s',
                        $context['dataset_id'],
                        number_format($context['variables']),
                        $this->duration(microtime(true) - $datasetStarted),
                        $context['failed'] ? " ({$context['failed']} rejected, see the log)" : '',
                    ));
                    $bar->setMessage('');
                    $bar->advance();
                    break;
                case 'skipped':
                    $tally['skipped'][$context['reason']] = ($tally['skipped'][$context['reason']] ?? 0) + 1;
                    if ($this->output->isVerbose()) {
                        $above("  - dataset {$context['dataset_id']}: skipped, {$context['reason']}");
                    }
                    $bar->advance();
                    break;
                case 'failed':
                    $tally['failed']++;
                    $above("  ✗ dataset {$context['dataset_id']}: {$context['error']}", 'error');
                    $bar->setMessage('');
                    $bar->advance();
                    break;
            }
        }, $resume);

        $bar?->finish();
        $this->newLine(2);

        $this->info(sprintf(
            'Done in %s. %s variables indexed from %d %s.',
            $this->duration(microtime(true) - $started),
            number_format($total),
            $tally['indexed'],
            Str::plural('dataset', $tally['indexed']),
        ));
        foreach ($tally['skipped'] as $reason => $count) {
            $this->line("  Skipped {$count}: {$reason}.");
        }

        if ($tally['failed'] > 0) {
            $this->error("{$tally['failed']} datasets failed. Run again with --resume to retry only those.");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function duration(float $seconds): string
    {
        if ($seconds < 60) {
            return sprintf('%.1fs', $seconds);
        }

        $whole = (int) $seconds;
        if ($whole < 3600) {
            return sprintf('%dm %02ds', intdiv($whole, 60), $whole % 60);
        }

        return sprintf('%dh %02dm', intdiv($whole, 3600), intdiv($whole % 3600, 60));
    }
}
