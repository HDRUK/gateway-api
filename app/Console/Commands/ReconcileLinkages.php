<?php

namespace App\Console\Commands;

use App\Jobs\LinkageExtraction;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Services\Gwdm\GwdmHandlerFactory;
use Illuminate\Console\Command;

/**
 * Reconcile the linkage junction tables against the stored GWDM blob (GAT-9018).
 *
 * Since the junction tables became the source of truth for 2.x linkages, any
 * reference LinkageExtraction could not resolve at write time (a free-text dataset
 * title, or a DOI with no matching publications row) is preserved as a junction row
 * with a NULL target FK and the raw value in raw_url/raw_pid/raw_title/raw_doi
 * (see the 2026_06_18 migration + Gwdm2xHandler).
 *
 * Rows extracted BEFORE that change simply dropped those unresolved references, so
 * they now live only in the JSON blob and are invisible to afterRead(). Re-running
 * extraction rebuilds each version's junction rows from the reconstructed blob
 * (delete-then-insert per version — idempotent), backfilling the unresolved refs.
 *
 * This is a manual ops tool: nothing dispatches it automatically. Start with
 * --dry-run to see the scope, then run for real (optionally --sync for small runs).
 */
class ReconcileLinkages extends Command
{
    protected $signature = 'app:reconcile-linkages
        {--dataset= : Limit to a single dataset id}
        {--all-versions : Process every version of each dataset (default: latest version only)}
        {--sync : Run extraction inline instead of dispatching to the enrichment queue}
        {--dry-run : Report what would be processed without changing anything}
        {--chunk=100 : Dataset chunk size when iterating}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Re-run linkage extraction to backfill unresolved (free-text / unknown-DOI) linkages into the junction tables from the GWDM blob (GAT-9018).';

    public function handle(): int
    {
        $datasetId = $this->option('dataset');
        $allVersions = (bool) $this->option('all-versions');
        $sync = (bool) $this->option('sync');
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        $query = Dataset::query()->select(['id']);
        if ($datasetId !== null) {
            $query->where('id', (int) $datasetId);
        }

        $datasetCount = (clone $query)->count();
        if ($datasetCount === 0) {
            $this->warn('No datasets matched — nothing to do.');

            return self::SUCCESS;
        }

        $mode = $dryRun ? 'DRY RUN (no changes)' : ($sync ? 'SYNC (inline)' : 'QUEUED (enrichment)');
        $scope = $allVersions ? 'all versions' : 'latest version only';
        $this->info("Reconciling linkages for {$datasetCount} dataset(s) — {$scope} — mode: {$mode}.");

        if (! $dryRun && ! $this->option('force')
            && ! $this->confirm("Re-run linkage extraction for {$datasetCount} dataset(s)? Existing junction rows are rebuilt from the GWDM blob.")) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        $versionsProcessed = 0;
        $failures = 0;

        $query->chunkById($chunk, function ($datasets) use ($allVersions, $sync, $dryRun, &$versionsProcessed, &$failures) {
            foreach ($datasets as $dataset) {
                $versions = $this->versionsFor($dataset, $allVersions);

                foreach ($versions as $version) {
                    $versionsProcessed++;

                    if ($dryRun) {
                        $this->line("  would reconcile dataset {$dataset->id} version {$version->id} (v{$version->version}, gwdm {$version->gwdm_version})");

                        continue;
                    }

                    try {
                        if ($sync) {
                            // Run extraction inline via the handler (bypasses the queue
                            // entirely) — mirrors what LinkageExtraction::handle() does.
                            $full = DatasetVersion::findOrFail($version->id);
                            $gwdmVersion = $full->gwdm_version ?? ($full->metadata['gwdmVersion'] ?? '2.0');
                            app(GwdmHandlerFactory::class)->resolve($gwdmVersion)->extractLinkages($full);
                        } else {
                            LinkageExtraction::dispatch((string) $dataset->id, (string) $version->id);
                        }
                    } catch (\Throwable $e) {
                        $failures++;
                        $this->error("  failed dataset {$dataset->id} version {$version->id}: {$e->getMessage()}");
                    }
                }
            }
        });

        $verb = $dryRun ? 'would process' : ($sync ? 'processed' : 'dispatched');
        $this->info("Done. {$verb} {$versionsProcessed} version(s)".($failures ? " with {$failures} failure(s)." : '.'));

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * The dataset versions to reconcile: the latest only, or every version.
     *
     * @return iterable<DatasetVersion>
     */
    private function versionsFor(Dataset $dataset, bool $allVersions): iterable
    {
        if ($allVersions) {
            return DatasetVersion::where('dataset_id', $dataset->id)
                ->select(['id', 'version', 'gwdm_version'])
                ->orderBy('version')
                ->get();
        }

        $latest = $dataset->latestVersion(['id', 'version', 'gwdm_version', 'dataset_id']);

        return $latest ? [$latest] : [];
    }
}
