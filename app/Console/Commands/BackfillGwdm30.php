<?php

namespace App\Console\Commands;

use App\Models\DatasetVersion;
use App\Services\DatasetService;
use App\Services\Gwdm\GwdmHandlerFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use MetadataManagementController as MMC;

class BackfillGwdm30 extends Command
{
    protected $signature = 'app:backfill-gwdm30 {--dry-run : Preview without writing}';

    protected $description = 'Translate existing GWDM 2.1 dataset versions to 3.0 and persist to SQL tables';

    public function __construct(
        private readonly DatasetService $datasetService,
        private readonly GwdmHandlerFactory $handlerFactory,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('[dry-run] No changes will be written.');
        }

        $total = DatasetVersion::where('gwdm_version', '2.1')->count();

        if ($total === 0) {
            $this->info('No GWDM 2.1 dataset versions found. Nothing to do.');
            return self::SUCCESS;
        }

        $this->info("Found {$total} GWDM 2.1 dataset version(s) to migrate.");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $migrated = 0;
        $failed   = 0;
        $handler  = $this->handlerFactory->resolve('3.0');

        DatasetVersion::where('gwdm_version', '2.1')
            ->with('dataset.team')
            ->orderBy('id')
            ->chunk(50, function ($versions) use ($dryRun, $handler, $bar, &$migrated, &$failed) {
                foreach ($versions as $dv) {
                    try {
                        $this->migrate($dv, $handler, $dryRun);
                        $migrated++;
                    } catch (\Throwable $e) {
                        $failed++;
                        $this->newLine();
                        $this->warn(
                            "  FAILED dataset_version_id={$dv->id} "
                            . "(dataset_id={$dv->dataset_id}, version={$dv->version}): "
                            . $e->getMessage()
                        );
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();

        $action = $dryRun ? 'would be migrated' : 'migrated';
        $this->info("Done. {$migrated} {$action}, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function migrate(DatasetVersion $dv, $handler, bool $dryRun): void
    {
        $dataset = $dv->dataset;
        $team    = $dataset->team;

        // Step 1: Reconstruct the stored 2.1 envelope.
        $envelope21 = $this->datasetService->getReconstructedMetadataEnvelope(
            $dv->dataset_id,
            $dv->version,
        );

        // Step 2: Translate 2.1 → 3.0 via TRASER.
        $translated = MMC::translateDataModelType(
            json_encode($envelope21),
            'GWDM',
            '3.0',
            'GWDM',
            '2.1',
        );

        if (!$translated['wasTranslated']) {
            throw new \RuntimeException('TRASER translation failed');
        }

        // Step 3: Apply version-specific mutations (required block, publisher).
        $gwdm30 = $handler->prepareMetadata(
            $translated['metadata'],
            $dataset,
            $team,
            $dv->version,
        );

        if ($dryRun) {
            return;
        }

        // Step 4: Persist — update the row and write to gwdm30_* SQL tables.
        DB::transaction(function () use ($dv, $handler, $gwdm30, $envelope21) {
            [$title, $shortTitle] = $handler->extractTitleFields($gwdm30);
            $envelope30 = $handler->buildEnvelope($gwdm30, $envelope21['original_metadata']);

            $dv->metadata     = json_encode($envelope30);
            $dv->gwdm_version = '3.0';
            $dv->title        = $title;
            $dv->short_title  = $shortTitle;
            $dv->save();

            $handler->afterStore($dv->dataset, $dv, $gwdm30);
        });
    }
}
