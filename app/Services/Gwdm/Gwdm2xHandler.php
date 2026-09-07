<?php

namespace App\Services\Gwdm;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasDatasetVersion;
use App\Models\Publication;
use App\Models\PublicationHasDatasetVersion;
use App\Models\Team;
use App\Services\DatasetService;
use Illuminate\Support\Arr;
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
        // A `linkage` key absent entirely means this version's metadata never touched
        // linkage (e.g. a partial update) — leave existing junction rows untouched rather
        // than treating omission as an intentional clear. A present-but-empty sub-array
        // still clears, as before.
        if (! array_key_exists('linkage', $gwdm)) {
            return;
        }

        $linkage = $gwdm['linkage'];

        if (array_key_exists('datasetLinkage', $linkage)) {
            $datasetLinkages = $linkage['datasetLinkage'];
            $datasetLinkages = $datasetLinkages !== '' ? $datasetLinkages : null;
            $this->processDatasetLinkages($dv->id, $datasetLinkages);
        }

        if (array_key_exists('publicationAboutDataset', $linkage)) {
            $aboutLinkages = $linkage['publicationAboutDataset'];
            $aboutLinkages = $aboutLinkages !== '' ? $aboutLinkages : null;
            $this->processPublicationLinkages($dv->id, $aboutLinkages, 'ABOUT');
        }

        if (array_key_exists('publicationUsingDataset', $linkage)) {
            $usingLinkages = $linkage['publicationUsingDataset'];
            $usingLinkages = $usingLinkages !== '' ? $usingLinkages : null;
            $this->processPublicationLinkages($dv->id, $usingLinkages, 'USING');
        }
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
                    // Store unresolved reference so afterRead() can reconstruct it from SQL.
                    // Coerce blanks to NULL: the GWDM schema allows null url/pid/title but a
                    // string must satisfy minLength/uri format, so '' would fail validation.
                    DatasetVersionHasDatasetVersion::create([
                        'dataset_version_source_id' => $sourceVersionId,
                        'dataset_version_target_id' => null,
                        'linkage_type' => $key,
                        'direct_linkage' => 1,
                        'description' => self::LINKAGE_DESCRIPTION,
                        'raw_url' => $this->blankToNull($d['url'] ?? null),
                        'raw_pid' => $this->blankToNull($d['pid'] ?? null),
                        'raw_title' => $this->blankToNull($d['title'] ?? null),
                    ]);

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
                // Store unresolved DOI so afterRead() can reconstruct it from SQL.
                PublicationHasDatasetVersion::create([
                    'publication_id' => null,
                    'dataset_version_id' => $sourceVersionId,
                    'link_type' => $linkType,
                    'description' => self::LINKAGE_DESCRIPTION,
                    'raw_doi' => $doi,
                ]);

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

    /**
     * Reduce a stored `paper_doi` to the bare DOI form the GWDM Doi schema requires.
     * `paper_doi` is stored inconsistently (bare, `doi.org/...`, or `https://doi.org/...`);
     * strip any doi.org host prefix — with or without a scheme — so reconstructed linkage
     * arrays validate (mirrors the prefix stripping in findTargetPublication()). Returns null
     * for empty input so callers can skip it.
     */
    protected function normalisePublicationDoi(?string $doi): ?string
    {
        if (! $doi) {
            return null;
        }

        $doi = trim($doi);
        $doi = preg_replace('#^(https?://)?(dx\.)?doi\.org/#i', '', $doi);

        return $doi !== '' ? $doi : null;
    }

    /**
     * Normalise a raw linkage string for storage/output: trim and treat an empty
     * string as NULL. The GWDM schema allows null url/pid/title but requires any
     * string to satisfy minLength/uri constraints, so '' would fail validation.
     */
    protected function blankToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * Unresolved (free-text) dataset linkage rows for a version — junction rows with
     * dataset_version_target_id = NULL that carry the raw url/pid/title captured at
     * extraction time. resolveDatasetLinkages() excludes these (it requires an ACTIVE
     * target), so afterRead()/getLinkages() read them here to keep SQL the complete
     * source of truth.
     *
     * @return array<int, object{linkage_type: string, raw_url: ?string, raw_pid: ?string, raw_title: ?string}>
     */
    protected function resolveUnresolvedDatasetLinkages(int $sourceVersionId): array
    {
        return DB::select(
            'SELECT linkage_type, raw_url, raw_pid, raw_title
            FROM dataset_version_has_dataset_version
            WHERE dataset_version_source_id = ?
              AND direct_linkage = ?
              AND description = ?
              AND dataset_version_target_id IS NULL',
            [$sourceVersionId, 1, self::LINKAGE_DESCRIPTION]
        );
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
        $resolvedDatasets = $this->resolveDatasetLinkages($dv->id, useLatestTitle: true);

        // Unresolved rows (target_id = NULL) carry the raw free-text reference captured
        // at extraction time. resolveDatasetLinkages() intentionally excludes them (it
        // requires an ACTIVE target dataset), so read them separately to preserve them.
        $unresolvedDatasets = $this->resolveUnresolvedDatasetLinkages($dv->id);

        $publications = $this->resolvePublicationLinkages($dv->id);

        if (empty($resolvedDatasets) && empty($unresolvedDatasets) && empty($publications)) {
            return [];
        }

        $datasetLinkage = [];
        foreach ($resolvedDatasets as $row) {
            $datasetLinkage[$row->linkage_type][] = [
                'url' => config('gateway.gateway_url').'/en/dataset/'.$row->dataset_id,
                'pid' => $row->pid,
                'title' => $row->title,
            ];
        }
        foreach ($unresolvedDatasets as $row) {
            // blankToNull: GWDM allows null url/pid/title, but '' fails minLength/uri.
            $datasetLinkage[$row->linkage_type][] = [
                'url' => $this->blankToNull($row->raw_url),
                'pid' => $this->blankToNull($row->raw_pid),
                'title' => $this->blankToNull($row->raw_title),
            ];
        }

        $aboutDataset = [];
        $usingDataset = [];
        foreach ($publications as $row) {
            // `paper_doi` is stored inconsistently — some rows carry a bare DOI, others a
            // `https://doi.org/...` URL. The GWDM Doi schema only accepts the bare form, so
            // normalise here (mirrors the prefix stripping in findTargetPublication()).
            $doi = $this->normalisePublicationDoi($row->doi);
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
                // GWDM schema requires datasetLinkage to be an object or null.
                // An empty PHP array JSON-encodes to `[]`, which fails validation,
                // so collapse the no-linkages case to null. A populated linkage has
                // string keys (linkage_type) and correctly encodes to an object.
                'datasetLinkage' => empty($datasetLinkage) ? null : $datasetLinkage,
                'publicationAboutDataset' => $aboutDataset,
                'publicationUsingDataset' => $usingDataset,
            ],
        ];
    }

    /**
     * Single source of truth for dataset-linkage row selection, shared by
     * afterRead() (GWDM metadata block) and getLinkages() (flat attribute).
     * SQL is authoritative for 2.x linkage data on reads.
     *
     * Rules:
     *   - direct_linkage = 1 rows for the given source version.
     *   - Target resolved via target version -> dataset_id -> datasets using LEFT
     *     joins (robust to a missing/hard-deleted target version row).
     *   - Only targets that exist (not soft-deleted) and are ACTIVE are returned;
     *     unresolved / archived / deleted targets are dropped.
     *   - Title tracks the target dataset's CURRENT latest short_title when
     *     $useLatestTitle is true, so it stays consistent with the dataset-level URL;
     *     otherwise (default) the frozen short_title captured at extraction time is
     *     used as-is. When $useLatestTitle is true, the frozen short_title is still
     *     the fallback if the latest lookup misses.
     *
     * @return array<int, object{linkage_type: string, dataset_id: int, pid: string, title: ?string}>
     */
    protected function resolveDatasetLinkages(int $sourceVersionId, bool $useLatestTitle = false): array
    {
        $rows = collect(DB::select(
            'SELECT
                dataset_version_has_dataset_version.linkage_type,
                datasets.id AS dataset_id,
                datasets.pid,
                dataset_versions.short_title AS frozen_short_title
            FROM dataset_version_has_dataset_version
            LEFT JOIN dataset_versions
                ON dataset_versions.id = dataset_version_has_dataset_version.dataset_version_target_id
               AND dataset_versions.deleted_at IS NULL
            LEFT JOIN datasets
                ON datasets.id = dataset_versions.dataset_id
               AND datasets.deleted_at IS NULL
            WHERE dataset_version_has_dataset_version.dataset_version_source_id = ?
              AND dataset_version_has_dataset_version.direct_linkage = ?
              AND datasets.status = ?',
            [$sourceVersionId, 1, Dataset::STATUS_ACTIVE]
        ));

        // this is a refactor candidate
        // - there are inconsistencies with the dataset title in the linkages if a new dataset version
        //   is published and changes the title (unlikely)
        // - all due to linkages being on dataset-version rather that dataset
        // - for now, keep at it is, but a method has been added to look up the latest tittle
        $latestTitles = $useLatestTitle
            ? $this->latestShortTitlesFor($rows->pluck('dataset_id')->filter()->unique()->all())
            : [];

        return $rows
            ->map(fn ($row) => (object) [
                'linkage_type' => $row->linkage_type,
                'dataset_id' => (int) $row->dataset_id,
                'pid' => $row->pid,
                'title' => $latestTitles[$row->dataset_id] ?? $row->frozen_short_title,
            ])
            ->values()
            ->all();
    }

    /**
     * Publication linkages for the given source version, sourced from SQL.
     * Companion to resolveDatasetLinkages(); consumed by afterRead() to rebuild the
     * publicationAboutDataset / publicationUsingDataset arrays.
     *
     * Uses a LEFT JOIN and coalesces the resolved publication's paper_doi with the
     * raw_doi captured at extraction time, so unresolved (free-text) DOIs survive the
     * read the same way unresolved dataset linkages do.
     *
     * @return array<int, object{link_type: string, doi: string}>
     */
    protected function resolvePublicationLinkages(int $sourceVersionId): array
    {
        return DB::select(
            'SELECT
                publication_has_dataset_version.link_type,
                COALESCE(publications.paper_doi, publication_has_dataset_version.raw_doi) AS doi
            FROM publication_has_dataset_version
            LEFT JOIN publications
                ON publications.id = publication_has_dataset_version.publication_id
               AND publications.deleted_at IS NULL
            WHERE publication_has_dataset_version.dataset_version_id = ?
              AND publication_has_dataset_version.description = ?
              AND publication_has_dataset_version.deleted_at IS NULL',
            [$sourceVersionId, self::LINKAGE_DESCRIPTION]
        );
    }

    /**
     * Flat dataset-linkage list for the given version (frontend `linkages` attribute).
     * Formats resolveDatasetLinkages() and appends unresolved (free-text) rows so a
     * linkage to a dataset not on the gateway still surfaces its raw title/url.
     */
    public function getLinkages(int $datasetVersionId): array
    {
        $resolved = array_map(
            fn ($row) => [
                'title' => $row->title,
                'url' => config('gateway.gateway_url').'/en/dataset/'.$row->dataset_id,
                'dataset_id' => $row->dataset_id,
                'linkage_type' => $row->linkage_type,
            ],
            $this->resolveDatasetLinkages($datasetVersionId, useLatestTitle: true)
        );

        $unresolved = array_map(
            fn ($row) => [
                'title' => $this->blankToNull($row->raw_title),
                'url' => $this->blankToNull($row->raw_url),
                'dataset_id' => null,
                'linkage_type' => $row->linkage_type,
            ],
            $this->resolveUnresolvedDatasetLinkages($datasetVersionId)
        );

        return array_merge($resolved, $unresolved);
    }

    /**
     * DRY-RUN diagnostics (read-only): which references in the reconstructed GWDM blob
     * are NOT represented by a live junction row, and why. Powers
     * `app:reconcile-linkages --dry-run`. A non-empty result means SQL is out of sync
     * with the blob; a reconcile (re-extraction) would backfill these.
     *
     * @return array<int, array{kind: string, linkage_type: string, reference: string, reason: string}>
     */
    public function diagnoseLinkageDrift(DatasetVersion $dv): array
    {
        // Blob linkage only ($applySupplementary = false) — i.e. what extraction reads.
        $gwdm = $this->datasetService()->getReconstructedMetadataEnvelope(
            $dv->dataset_id,
            $dv->version,
            false,
            $dv,
            false,
        )['metadata'] ?? [];

        $linkage = $gwdm['linkage'] ?? [];
        $drift = [];

        foreach (['publicationAboutDataset' => 'ABOUT', 'publicationUsingDataset' => 'USING'] as $key => $linkType) {
            $dois = $linkage[$key] ?? null;
            if (! is_array($dois)) {
                continue;
            }

            $represented = $this->representedPublicationDois($dv->id, $linkType);

            foreach ($dois as $doi) {
                if (! is_string($doi) || trim($doi) === '') {
                    continue;
                }
                $bare = $this->normalisePublicationDoi($doi);
                if ($bare !== null && in_array($bare, $represented, true)) {
                    continue; // in sync
                }
                $drift[] = [
                    'kind' => 'publication',
                    'linkage_type' => $linkType,
                    'reference' => $doi,
                    'reason' => $this->publicationDriftReason($dv->id, $bare, $linkType),
                ];
            }
        }

        $datasetLinkage = $linkage['datasetLinkage'] ?? null;
        if (is_array($datasetLinkage)) {
            foreach ($datasetLinkage as $type => $refs) {
                if (! is_array($refs)) {
                    continue;
                }
                foreach ($refs as $ref) {
                    if (! is_array($ref)) {
                        continue;
                    }
                    $reason = $this->datasetDriftReason($dv->id, $ref, (string) $type);
                    if ($reason === null) {
                        continue; // in sync
                    }
                    $drift[] = [
                        'kind' => 'dataset',
                        'linkage_type' => (string) $type,
                        'reference' => (string) ($ref['url'] ?? $ref['pid'] ?? $ref['title'] ?? '(unknown)'),
                        'reason' => $reason,
                    ];
                }
            }
        }

        return $drift;
    }

    /** Bare DOIs already represented by a live publication junction row for this version + link type. */
    protected function representedPublicationDois(int $versionId, string $linkType): array
    {
        $rows = DB::select(
            'SELECT COALESCE(publications.paper_doi, publication_has_dataset_version.raw_doi) AS doi
            FROM publication_has_dataset_version
            LEFT JOIN publications
                ON publications.id = publication_has_dataset_version.publication_id
               AND publications.deleted_at IS NULL
            WHERE publication_has_dataset_version.dataset_version_id = ?
              AND publication_has_dataset_version.description = ?
              AND publication_has_dataset_version.link_type = ?
              AND publication_has_dataset_version.deleted_at IS NULL',
            [$versionId, self::LINKAGE_DESCRIPTION, $linkType]
        );

        return array_values(array_filter(array_map(
            fn ($r) => $this->normalisePublicationDoi($r->doi),
            $rows
        )));
    }

    /** Why a blob DOI is missing from SQL (only called when it is not represented). */
    protected function publicationDriftReason(int $versionId, ?string $bareDoi, string $linkType): string
    {
        $trashed = PublicationHasDatasetVersion::onlyTrashed()
            ->where('dataset_version_id', $versionId)
            ->where('description', self::LINKAGE_DESCRIPTION)
            ->where('link_type', $linkType)
            ->get();

        foreach ($trashed as $row) {
            $doi = $row->raw_doi
                ?? optional(Publication::withTrashed()->find($row->publication_id))->paper_doi;
            if ($doi !== null && $this->normalisePublicationDoi($doi) === $bareDoi) {
                return 'linkage soft-deleted';
            }
        }

        if ($bareDoi !== null && $this->findTargetPublication($bareDoi)) {
            return 'publication exists but not linked';
        }

        return 'no publication row (reconcile stores raw DOI)';
    }

    /** Why a blob dataset reference is missing from SQL, or null when it is represented. */
    protected function datasetDriftReason(int $versionId, array $ref, string $type): ?string
    {
        $targetVersionId = $this->findTargetDataset($ref);

        if ($targetVersionId) {
            $linked = DatasetVersionHasDatasetVersion::query()
                ->where('dataset_version_source_id', $versionId)
                ->where('dataset_version_target_id', $targetVersionId)
                ->where('linkage_type', $type)
                ->where('direct_linkage', 1)
                ->where('description', self::LINKAGE_DESCRIPTION)
                ->exists();
            if ($linked) {
                return null;
            }

            $targetVersion = DatasetVersion::find($targetVersionId);
            $targetDataset = $targetVersion ? Dataset::find($targetVersion->dataset_id) : null;
            if (! $targetDataset || $targetDataset->status !== Dataset::STATUS_ACTIVE) {
                return 'target dataset not active (excluded from reconstruction)';
            }

            return 'dataset exists but not linked';
        }

        // Unresolvable free-text reference: represented by a live raw_* row?
        $matched = false;
        $query = DatasetVersionHasDatasetVersion::query()
            ->where('dataset_version_source_id', $versionId)
            ->whereNull('dataset_version_target_id')
            ->where('linkage_type', $type)
            ->where('direct_linkage', 1)
            ->where('description', self::LINKAGE_DESCRIPTION)
            ->where(function ($q) use ($ref, &$matched) {
                foreach (['raw_url' => 'url', 'raw_pid' => 'pid', 'raw_title' => 'title'] as $col => $k) {
                    if (! empty($ref[$k])) {
                        $q->orWhere($col, $ref[$k]);
                        $matched = true;
                    }
                }
                if (! $matched) {
                    $q->whereRaw('1 = 0');
                }
            });

        if ($matched && $query->exists()) {
            return null;
        }

        return 'unresolvable reference (reconcile stores raw url/pid/title)';
    }

    /**
     * Latest-version short_title for each given dataset id, keyed by dataset id.
     *
     * Linkage junction rows freeze a `dataset_version_target_id` (whatever was latest at
     * extraction time), but the read-back URL resolves to the dataset — i.e. its current
     * latest version. Resolving titles from the latest version here keeps the displayed
     * title consistent with the URL once the target dataset gains newer versions.
     *
     * Reuses Dataset::latestMetadata() (latestOfMany('version'), soft-delete aware).
     *
     * @param  array<int, int>  $datasetIds
     * @return array<int, string|null>
     */
    protected function latestShortTitlesFor(array $datasetIds): array
    {
        if (empty($datasetIds)) {
            return [];
        }

        return Dataset::whereIn('id', $datasetIds)
            ->with('latestMetadata')
            ->get()
            ->mapWithKeys(fn (Dataset $d) => [$d->id => $d->latestMetadata?->short_title])
            ->all();
    }

    /**
     * 2.x stores biological material types under `tissuesSampleCollection`.
     *
     * @param  array<string, mixed>  $metadata  inner GWDM metadata block
     * @return array<int, string>|null
     */
    public function getMaterialTypes(array $metadata): ?array
    {
        $tissues = Arr::get($metadata, 'tissuesSampleCollection', null);
        if (is_null($tissues)) {
            return null;
        }

        $materialTypes = array_reduce($tissues, function ($carry, $item) {
            if (($item['materialType'] ?? '') !== 'None/not available') {
                $carry[] = $item['materialType'];
            }

            return $carry;
        }, []);

        // array_values() so array_unique()'s gapped keys don't serialise as a JSON object.
        return count($materialTypes) === 0 ? null : array_values(array_unique($materialTypes));
    }
}
