<?php

namespace App\Services\Gwdm;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasDatasetVersion;
use App\Models\Publication;
use App\Models\PublicationHasDatasetVersion;
use App\Models\Team;
use App\Services\DatasetService;
use Illuminate\Support\Facades\DB;

/**
 * Handler for GWDM >= 1.1, covering versions 2.0 and 2.1.
 *
 * Differences from 1.x:
 *   - summary.publisher uses the NEW format: { gatewayId, name }
 *   - required block includes a 'version' field ("X.0.0")
 *
 * 2.0 and 2.1 share the same JSON blob storage strategy — both use
 * the metadata column with RFC 6902 delta compression. Schema-level
 * differences (new/changed GWDM fields in 2.1) are handled by TRASER,
 * not by this handler.
 */
class Gwdm2xHandler extends GwdmMetadataHandler
{
    /**
     * Build summary.publisher = { gatewayId, name }.
     *
     * Keeps the publisher from the incoming metadata payload when its gatewayId
     * maps to an existing team (validated by casting to an integer team id and
     * looking it up); otherwise falls back to the requesting team.
     *
     * KNOWN ISSUE: gatewayId here is the raw team primary key (e.g. "07"), not
     * the team's persistent id (pid) — inconsistent with the rest of the model,
     * and nothing checks the requesting team is allowed to publish as the named
     * team.
     */
    public function buildPublisher(Team $team, array $incoming = []): array
    {
        $gatewayId = $incoming['gatewayId'] ?? null;

        // Valid only if the gatewayId maps to an existing team (by integer id).
        if ($gatewayId !== null && Team::find((int) $gatewayId)) {
            return $incoming;
        }

        return [
            'gatewayId' => (string) $team->id,
            'name' => $team->name,
        ];
    }

    /**
     * Single named resolution point for DatasetService within this handler.
     *
     * Constructor injection isn't viable: this handler is instantiated via
     * `new Gwdm20Handler($version)` in GwdmHandlerFactory::resolve() with a
     * runtime-only $version string, not container-resolved. DatasetService
     * already depends on GwdmHandlerFactory to create these handlers, so
     * injecting DatasetService back into the factory (to pass down here)
     * would be circular.
     */
    protected function datasetService(): DatasetService
    {
        return app(DatasetService::class);
    }

    public function buildRequiredBlock(Dataset $dataset, int $versionNumber): array
    {
        return [
            'gatewayId' => strval($dataset->id),
            'gatewayPid' => $dataset->pid,
            'issued' => $dataset->created,
            'modified' => $dataset->updated,
            'version' => $this->formatVersion($versionNumber),
            'revisions' => $this->buildRevisions($dataset, $versionNumber),
        ];
    }

    public function prepareMetadata(array $gwdm, Dataset $dataset, Team $team, int $versionNumber): array
    {
        $required = $this->buildRequiredBlock($dataset, $versionNumber);

        // Preserve any version string the caller (or TRASER) already set,
        // falling back to the computed one from the DB version number.
        // DB-derived values win for all other required keys.
        $required['version'] = $gwdm['required']['version'] ?? $required['version'];

        $gwdm['required'] = array_merge($gwdm['required'] ?? [], $required);
        $gwdm['summary']['publisher'] = $this->buildPublisher($team, $gwdm['summary']['publisher'] ?? []);

        return $gwdm;
    }

    // ── Linkage extraction ────────────────────────────────────────────────────

    public const LINKAGE_DESCRIPTION = 'Extracted from GWDM';

    public function extractLinkages(DatasetVersion $dv): void
    {
        // Read the linkage section from the RECONSTRUCTED GWDM, not the raw
        // metadata column: delta rows store only a patch (metadata = []), so
        // reading the column directly would wipe existing linkage on every
        // delta update. Reconstruction replays the delta chain to a full object.
        //
        // $applySupplementary = false: this method is the WRITER of the linkage
        // junction tables. afterRead() rebuilds gwdm['linkage'] from those same
        // tables, so applying it here would read back stale/soon-to-be-deleted
        // linkage and clobber the freshly-authored linkage on a re-dispatch.
        $gwdm = $this->datasetService()->getReconstructedMetadataEnvelope(
            $dv->dataset_id,
            $dv->version,
            false,
            $dv,
            false,
        )['metadata'] ?? [];

        $this->writeLinkages($dv, $gwdm);
    }

    /**
     * Write dataset and publication linkage junction rows from a full GWDM array.
     *
     * Separated from extractLinkages() so GWDM 3.0 can invoke it synchronously
     * from afterStore() with the input metadata — its linkage arrays are not
     * recoverable via reconstruction (persist() does not store them, and the
     * read path rebuilds them from the very junction tables written here).
     */
    protected function writeLinkages(DatasetVersion $dv, array $gwdm): void
    {
        $linkage = $gwdm['linkage'] ?? [];

        $datasetLinkages = $linkage['datasetLinkage'] ?? null;
        $datasetLinkages = $datasetLinkages !== '' ? $datasetLinkages : null;

        $aboutLinkages = $linkage['publicationAboutDataset'] ?? null;
        $aboutLinkages = $aboutLinkages !== '' ? $aboutLinkages : null;

        $usingLinkages = $linkage['publicationUsingDataset'] ?? null;
        $usingLinkages = $usingLinkages !== '' ? $usingLinkages : null;

        $this->processDatasetLinkages($dv->id, $datasetLinkages);
        $this->processPublicationLinkages($dv->id, $aboutLinkages, 'ABOUT');
        $this->processPublicationLinkages($dv->id, $usingLinkages, 'USING');
    }

    protected function processDatasetLinkages(int $sourceVersionId, ?array $datasetLinkages): void
    {
        DatasetVersionHasDatasetVersion::where([
            'dataset_version_source_id' => $sourceVersionId,
            'direct_linkage' => 1,
            'description' => self::LINKAGE_DESCRIPTION,
        ])->delete();

        if (is_null($datasetLinkages)) {
            return;
        }

        foreach ($datasetLinkages as $key => $data) {
            if (! $data) {
                continue;
            }
            foreach ($data as $d) {
                $targetVersionId = $this->findTargetDataset($d);

                if (! $targetVersionId) {
                    continue;
                }

                DatasetVersionHasDatasetVersion::firstOrCreate([
                    'dataset_version_source_id' => $sourceVersionId,
                    'dataset_version_target_id' => $targetVersionId,
                    'linkage_type' => $key,
                    'direct_linkage' => 1,
                    'description' => self::LINKAGE_DESCRIPTION,
                ]);
            }
        }
    }

    protected function processPublicationLinkages(int $sourceVersionId, ?array $publicationLinkages, string $linkType): void
    {
        PublicationHasDatasetVersion::where([
            'dataset_version_id' => $sourceVersionId,
            'description' => self::LINKAGE_DESCRIPTION,
            'link_type' => $linkType,
        ])->delete();

        if (is_null($publicationLinkages)) {
            return;
        }

        foreach ($publicationLinkages as $doi) {
            if (! $doi) {
                continue;
            }

            $publicationId = $this->findTargetPublication($doi);

            if (! $publicationId) {
                continue;
            }

            $linkage = PublicationHasDatasetVersion::withTrashed()->firstOrCreate([
                'publication_id' => $publicationId,
                'dataset_version_id' => $sourceVersionId,
                'link_type' => $linkType,
                'description' => self::LINKAGE_DESCRIPTION,
            ]);

            if ($linkage->trashed()) {
                $linkage->restore();
            }
        }
    }

    protected function findTargetDataset(array $data): ?int
    {
        $id = $data['url'] ?? null;
        $pid = $data['pid'] ?? null;
        $title = $data['title'] ?? null;

        if ($id) {
            $urlParts = explode('/', parse_url($id, PHP_URL_PATH));
            $id = end($urlParts);
            $dataset = Dataset::find($id);
            if ($dataset) {
                return $dataset->latestVersionID($dataset->id);
            }
        }

        if ($pid) {
            $dataset = Dataset::where('pid', $pid)->first();
            if ($dataset) {
                return $dataset->latestVersionID($dataset->id);
            }
        }

        if ($title) {
            $datasetVersion = DatasetVersion::filterTitle($title)->first();
            if ($datasetVersion) {
                return $datasetVersion->id;
            }
        }

        return null;
    }

    protected function findTargetPublication(string $doi): ?int
    {
        $publication = Publication::whereRaw(
            "REPLACE(REPLACE(paper_doi, 'https://doi.org/', ''), 'doi.org/', '') = ?",
            [$doi]
        )->first();

        return $publication?->id;
    }

    // ── Read path ─────────────────────────────────────────────────────────────

    /**
     * Reconstruct the GWDM `linkage` section entirely from SQL junction tables,
     * making SQL the single source of truth for 2.x linkage data on reads.
     *
     * Returns [] (falls through to the stored JSON blob) when no extracted rows
     * exist — this covers legacy rows that pre-date this migration. Re-dispatching
     * LinkageExtraction for those versions will backfill the SQL rows.
     */
    public function afterRead(DatasetVersion $dv): array
    {
        $resolvedDatasets = collect(DB::select(
            'SELECT
                dataset_version_has_dataset_version.linkage_type,
                dv.short_title,
                d.pid,
                d.id AS dataset_id
            FROM dataset_version_has_dataset_version
            INNER JOIN dataset_versions AS dv ON dv.id = dataset_version_has_dataset_version.dataset_version_target_id
            INNER JOIN datasets AS d ON d.id = dv.dataset_id
            WHERE dataset_version_has_dataset_version.dataset_version_source_id = ?
              AND dataset_version_has_dataset_version.direct_linkage = ?
              AND dataset_version_has_dataset_version.description = ?',
            [$dv->id, 1, self::LINKAGE_DESCRIPTION]
        ));

        $publications = collect(DB::select(
            'SELECT *
            FROM publication_has_dataset_version
            INNER JOIN publications ON publications.id = publication_has_dataset_version.publication_id
            WHERE publication_has_dataset_version.dataset_version_id = ?
              AND publication_has_dataset_version.description = ?
              AND publication_has_dataset_version.deleted_at IS NULL',
            [$dv->id, self::LINKAGE_DESCRIPTION]
        ));

        $hasExtractedRows = $resolvedDatasets->isNotEmpty()
            || $publications->isNotEmpty();

        if (! $hasExtractedRows) {
            return [];
        }

        $datasetLinkage = [];
        foreach ($resolvedDatasets as $row) {
            $datasetLinkage[$row->linkage_type][] = [
                'url' => config('gateway.gateway_url').'/en/dataset/'.$row->dataset_id,
                'pid' => $row->pid,
                'title' => $row->short_title,
            ];
        }

        $aboutDataset = [];
        $usingDataset = [];
        foreach ($publications as $row) {
            $doi = $row->paper_doi;
            if (! $doi) {
                continue;
            }
            if ($row->link_type === 'ABOUT') {
                $aboutDataset[] = $doi;
            } else {
                $usingDataset[] = $doi;
            }
        }

        return [
            'linkage' => [
                'datasetLinkage' => $datasetLinkage,
                'publicationAboutDataset' => $aboutDataset,
                'publicationUsingDataset' => $usingDataset,
            ],
        ];
    }
}
