<?php

namespace App\Console\Commands;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Services\DatasetService;
use App\Services\Gwdm\GwdmHandlerFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use MetadataManagementController as MMC;

class BackfillGwdm30 extends Command
{
    protected $signature = 'app:backfill-gwdm30
        {--dry-run : Preview without writing}
        {--dataset-id= : Limit migration to a single dataset ID}
        {--force : Re-persist rows already at GWDM 3.0 (re-runs prepareMetadata + afterStore, skips TRASER)}
        {--min-dataset-id= : Resume from this datasets.id (inclusive)}
        {--max-dataset-id= : Stop after this datasets.id (inclusive)}';

    protected $description = 'Translate GWDM 2.x dataset versions to 3.0 and persist as new dataset_version rows';

    public function __construct(
        private readonly DatasetService $datasetService,
        private readonly GwdmHandlerFactory $handlerFactory,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun      = $this->option('dry-run');
        $datasetId   = $this->option('dataset-id');
        $force       = $this->option('force');
        $minDatasetId = $this->option('min-dataset-id');
        $maxDatasetId = $this->option('max-dataset-id');

        $logPath = storage_path('logs/backfill-gwdm30-' . date('Y-m-d_H-i-s') . '.log');
        $log     = fopen($logPath, 'w');
        $write   = function (string $line) use ($log): void {
            fwrite($log, $line . PHP_EOL);
        };

        $flags = implode(' ', array_filter([
            $dryRun       ? '--dry-run'                        : null,
            $force        ? '--force'                          : null,
            $datasetId    ? "--dataset-id={$datasetId}"        : null,
            $minDatasetId ? "--min-dataset-id={$minDatasetId}" : null,
            $maxDatasetId ? "--max-dataset-id={$maxDatasetId}" : null,
        ]));
        $write("backfill-gwdm30 {$flags}  started " . date('Y-m-d H:i:s'));
        $write(str_repeat('-', 80));

        $this->info("Log: {$logPath}");

        if ($dryRun) {
            $this->info('[dry-run] No changes will be written.');
        }

        $query = Dataset::query()
            ->where('status', 'ACTIVE')
            ->whereNull('deleted_at')
            ->unless($force, fn ($q) =>
                $q->whereDoesntHave('versions', fn ($q) => $q->where('gwdm_version', '3.0'))
            )
            ->when($datasetId, fn ($q) => $q->where('id', $datasetId))
            ->when($minDatasetId, fn ($q) => $q->where('id', '>=', $minDatasetId))
            ->when($maxDatasetId, fn ($q) => $q->where('id', '<=', $maxDatasetId))
            ->orderBy('id');

        $total = $query->count();

        if ($total === 0) {
            $this->info('No ACTIVE datasets require migration. Nothing to do.');

            if (!$force) {
                $already30 = Dataset::query()
                    ->where('status', 'ACTIVE')
                    ->whereNull('deleted_at')
                    ->when($datasetId, fn ($q) => $q->where('id', $datasetId))
                    ->whereHas('versions', fn ($q) => $q->where('gwdm_version', '3.0'))
                    ->count();

                if ($already30 > 0) {
                    $this->info("{$already30} dataset(s) already have a GWDM 3.0 version and were skipped. Use --force to re-persist them.");
                }
            }

            return self::SUCCESS;
        }

        $label = $force
            ? "Found {$total} ACTIVE dataset(s) to process (including those already at GWDM 3.0)."
            : "Found {$total} ACTIVE dataset(s) without a GWDM 3.0 version to migrate.";
        $this->info($label);

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $migrated       = 0;
        $failed         = 0;
        $handler        = $this->handlerFactory->resolve('3.0');
        $currentDataset = null;
        $lastDataset    = null;

        register_shutdown_function(function () use (&$currentDataset, &$lastDataset, $write, $log) {
            $error = error_get_last();
            if ($error !== null && ($error['type'] & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR)) !== 0) {
                $context = $currentDataset
                    ? "during dataset_id={$currentDataset->id}"
                    : ($lastDataset
                        ? "between datasets — last completed: dataset_id={$lastDataset->id}; resume with --min-dataset-id=" . ($lastDataset->id + 1)
                        : 'before any dataset was processed');
                $msg = "FATAL {$context}";
                fwrite(STDERR, PHP_EOL . "  {$msg}" . PHP_EOL);
                $write("[FATAL] {$msg}");
                $write("  {$error['message']} in {$error['file']}:{$error['line']}");
            }
            if (is_resource($log)) {
                fclose($log);
            }
        });

        $query->with('team')
            ->chunkById(50, function ($datasets) use ($dryRun, $force, $handler, $bar, $write, &$migrated, &$failed, &$currentDataset, &$lastDataset) {
                foreach ($datasets as $dataset) {
                    $currentDataset = $dataset;
                    try {
                        $this->migrateDataset($dataset, $handler, $dryRun, $force);
                        $migrated++;
                    } catch (\Throwable $e) {
                        $failed++;
                        $msg = "FAILED dataset_id={$dataset->id}: {$e->getMessage()}";
                        $this->newLine();
                        $this->warn("  {$msg}");
                        $write("[FAIL]  {$msg}");
                        $write($e->getTraceAsString());
                        $write('');
                    }
                    $lastDataset    = $dataset;
                    $currentDataset = null;
                    $bar->advance();
                }

                gc_collect_cycles();
            }, 'datasets.id', 'id');

        $bar->finish();
        $this->newLine();

        $action  = $dryRun ? 'would be migrated' : 'migrated';
        $summary = "Done. {$migrated} {$action}, {$failed} failed.";
        $this->info($summary);
        $write(str_repeat('-', 80));
        $write($summary . '  finished ' . date('Y-m-d H:i:s'));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function migrateDataset(Dataset $dataset, $handler, bool $dryRun, bool $force = false): void
    {
        $team = $dataset->team;

        if ($team === null) {
            throw new \RuntimeException('Dataset has no team — ACTIVE dataset should always have a team.');
        }

        // --force path: re-populate SQL tables for an existing 3.0 row (no TRASER, no new row).
        if ($force) {
            $existing30 = DatasetVersion::where('dataset_id', $dataset->id)
                ->where('gwdm_version', '3.0')
                ->orderBy('version', 'desc')
                ->first();

            if ($existing30) {
                $envelope = $this->datasetService->getReconstructedMetadataEnvelope(
                    $dataset->id,
                    $existing30->version,
                );

                $gwdm30 = $handler->prepareMetadata(
                    $envelope['metadata'],
                    $dataset,
                    $team,
                    $existing30->version,
                );

                // TRASER can lose summary.title; fall back to the best 2.x source version.
                if (empty($gwdm30['summary']['title'])) {
                    $source2x = DatasetVersion::where('dataset_id', $dataset->id)
                        ->where('gwdm_version', '2.1')
                        ->orderBy('version', 'desc')
                        ->first()
                        ?? DatasetVersion::where('dataset_id', $dataset->id)
                            ->where('gwdm_version', '2.0')
                            ->orderBy('version', 'desc')
                            ->first();

                    if ($source2x) {
                        $env2x = $this->datasetService->getReconstructedMetadataEnvelope($dataset->id, $source2x->version);
                        $gwdm30['summary']['title']     = $env2x['metadata']['summary']['title'] ?? null;
                        $gwdm30['summary']['shortTitle'] = $env2x['metadata']['summary']['shortTitle'] ?? null;
                    }
                }

                if ($dryRun) {
                    return;
                }

                DB::transaction(function () use ($existing30, $handler, $gwdm30, $dataset, $envelope) {
                    [$title, $shortTitle] = $handler->extractTitleFields($gwdm30);
                    $envelope30 = $handler->buildEnvelope($gwdm30, $envelope['original_metadata'] ?? []);
                    $existing30->title       = $title;
                    $existing30->short_title = $shortTitle;
                    $existing30->metadata    = json_encode($envelope30);
                    $existing30->save();
                    $handler->afterStore($dataset, $existing30, $gwdm30);
                });

                return;
            }
            // No 3.0 row yet — fall through to the standard insert path below.
        }

        // Standard path: find the best 2.x source, translate, create a new 3.0 row.

        // Step A — source version selection: prefer 2.1, fall back to 2.0.
        $sourceRow = DatasetVersion::where('dataset_id', $dataset->id)
            ->where('gwdm_version', '2.1')
            ->orderBy('version', 'desc')
            ->first()
            ?? DatasetVersion::where('dataset_id', $dataset->id)
                ->where('gwdm_version', '2.0')
                ->orderBy('version', 'desc')
                ->first();

        if ($sourceRow === null) {
            throw new \RuntimeException('No GWDM 2.x source version found for this dataset.');
        }

        // Step B — reconstruct full envelope and translate to GWDM 3.0 via TRASER.
        $envelope2x = $this->datasetService->getReconstructedMetadataEnvelope(
            $dataset->id,
            $sourceRow->version,
        );

        $translated = MMC::translateDataModelType(
            json_encode($envelope2x),
            'GWDM',
            '3.0',
            'GWDM',
            $sourceRow->gwdm_version,
            true,   // validateInput
            false,  // validateOutput — allow minor schema drift carried through by TRASER
        );

        if (!$translated['wasTranslated']) {
            throw new \RuntimeException(
                'TRASER translation failed (HTTP ' . $translated['statusCode'] . '): '
                . json_encode($translated['traser_message'])
            );
        }

        // Step C — apply version-specific mutations.
        $newVersionNumber = DatasetVersion::where('dataset_id', $dataset->id)->max('version') + 1;

        $gwdm30 = $handler->prepareMetadata(
            $translated['metadata'],
            $dataset,
            $team,
            $newVersionNumber,
        );

        // TRASER can lose summary.title during 2.x→3.0 translation; fall back to the 2.x source.
        if (empty($gwdm30['summary']['title'])) {
            $gwdm30['summary']['title'] = $envelope2x['metadata']['summary']['title'] ?? null;
        }
        if (empty($gwdm30['summary']['shortTitle'])) {
            $gwdm30['summary']['shortTitle'] = $envelope2x['metadata']['summary']['shortTitle'] ?? null;
        }

        // Step D — dry-run early exit.
        if ($dryRun) {
            return;
        }

        // Step E — insert the new 3.0 row and populate structured SQL tables.
        DB::transaction(function () use ($dataset, $handler, $gwdm30, $envelope2x, $newVersionNumber) {
            [$title, $shortTitle] = $handler->extractTitleFields($gwdm30);
            $envelope30 = $handler->buildEnvelope($gwdm30, $envelope2x['original_metadata']);

            $newDv = DatasetVersion::create([
                'dataset_id'   => $dataset->id,
                'metadata'     => json_encode($envelope30),
                'patch'        => null,   // always a full snapshot — schema-lineage break
                'version'      => $newVersionNumber,
                'title'        => $title,
                'short_title'  => $shortTitle,
                'gwdm_version' => '3.0',
            ]);

            $handler->afterStore($dataset, $newDv, $gwdm30);
        });
    }
}
