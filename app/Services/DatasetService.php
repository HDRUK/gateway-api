<?php

namespace App\Services;

use Config;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasDatasetVersion;
use App\Models\DatasetVersionHasSpatialCoverage;
use App\Models\DataAccessTemplate;
use App\Models\SpatialCoverage;
use App\Models\Team;
use App\Jobs\TermExtraction;
use App\Jobs\LinkageExtraction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MetadataManagementController as MMC;
use Swaggest\JsonDiff\JsonDiff;
use Swaggest\JsonDiff\JsonPatch;

class DatasetService
{
    /**
     * How many delta patches we store before a full snapshot is
     * written instead. Caps the worst case reconstruction cost.
     *
     * Intentionally not configurable within .env.
     */
    private const SNAPSHOT_INTERVAL = 10;

    public function list(
        ?string $filterStatus,
        ?string $filterTitle,
        bool $withMetadata,
        int $perPage
    ): LengthAwarePaginator {
        if (!empty($filterTitle)) {
            // Use a subquery for the status filter to avoid materialising all IDs.
            // The unique-per-dataset deduplication still requires PHP-side processing
            // because MySQL lacks a portable "latest version per group" without window
            // functions, so we load only the filtered version rows and deduplicate.
            $statusSubquery = Dataset::query()
                ->when($filterStatus, fn ($q) => $q->where('status', $filterStatus))
                ->select('id');

            $matchingIds = DatasetVersion::whereIn('dataset_id', $statusSubquery)
                ->filterTitle($filterTitle)
                ->select('dataset_id', 'version')
                ->orderBy('version', 'desc')
                ->get()
                ->unique('dataset_id')
                ->pluck('dataset_id');

            $query = Dataset::whereIn('id', $matchingIds);
        } else {
            $query = Dataset::query()
                ->when($filterStatus, fn ($q) => $q->where('status', $filterStatus));
        }

        if ($withMetadata) {
            $query->with('latestMetadata');
        }

        return $query->applySorting()->paginate($perPage, ['*'], 'page');
    }

    public function findActive(int $id): ?Dataset
    {
        return Dataset::with('team')->where('status', Dataset::STATUS_ACTIVE)->find($id);
    }

    /**
     * Resolve all attributes required for a detailed show response.
     *
     * Replaces DatasetsV2Helpers@getDatasetDetails. All counts are pre-computed
     * here so the Resource's toArray() runs no queries.
     *
     * @throws \InvalidArgumentException  when schema_model/schema_version are mismatched
     * @throws \RuntimeException          when MMC translation fails
     */
    public function prepareForShow(
        Dataset $dataset,
        ?string $outputSchemaModel = null,
        ?string $outputSchemaVersion = null,
    ): Dataset {
        $dataset->loadMissing('team');

        $latestVersionId = $dataset->latestVersionID($dataset->id);

        $activeCollections = $dataset->allActiveCollections ?? [];

        $dataset->durs_count         = $this->countActiveDurs($latestVersionId);
        $dataset->publications_count = $this->countActivePublications($latestVersionId);
        $dataset->tools_count        = count($dataset->allActiveTools);
        $dataset->collections_count  = count($activeCollections);
        $dataset->spatialCoverage    = $dataset->allSpatialCoverages ?? [];
        $dataset->durs               = $dataset->allActiveDurs ?? [];
        $dataset->publications       = $dataset->allActivePublications ?? [];
        $dataset->named_entities     = $dataset->allNamedEntities ?? [];
        $dataset->collections        = $activeCollections;
        $dataset->linkages           = $this->getLinkages($latestVersionId);

        if ($outputSchemaModel && $outputSchemaVersion) {
            $latestVersion = $dataset->latestVersion();
            // Reconstruct the full GWDM metadata for the latest version before
            // passing to TRASER; the version row may be a delta.
            $fullMetadata = $this->getReconstructedMetadataEnvelope($dataset->id, $latestVersion->version);
            $translated = MMC::translateDataModelType(
                json_encode($fullMetadata),
                $outputSchemaModel,
                $outputSchemaVersion,
                Config::get('metadata.GWDM.name'),
                Config::get('metadata.GWDM.version'),
            );

            if (!$translated['wasTranslated']) {
                throw new \RuntimeException('Failed to translate metadata to requested schema');
            }

            $withLinks = DatasetVersion::where('id', $latestVersion->id)
                ->with(['reducedLinkedDatasetVersions'])
                ->first();
            $withLinks->metadata = json_encode(['metadata' => $translated['metadata']]);
            $dataset->setRelation('versions', [$withLinks]);

        } elseif ($outputSchemaModel) {
            throw new \InvalidArgumentException('schema_model provided without schema_version');
        } elseif ($outputSchemaVersion) {
            throw new \InvalidArgumentException('schema_version provided without schema_model');
        } else {
            $withLinks = DatasetVersion::where('id', $latestVersionId)
                ->with(['linkedDatasetVersions'])
                ->first();

            if ($withLinks) {
                // If the latest version is a delta, inject the reconstructed full
                // metadata so the Resource sees a consistent envelope regardless of
                // whether the row is a snapshot or a delta.
                if ($withLinks->patch !== null) {
                    $withLinks->metadata = $this->getReconstructedMetadataEnvelope(
                        $dataset->id,
                        $withLinks->version
                    );
                }

                $dataset->setRelation('versions', [$withLinks]);
            }
        }

        $teamPublishedDARTemplates = DataAccessTemplate::where([
            ['team_id', $dataset->team->id],
            ['published', 1],
        ])->pluck('id');
        $dataset->team->has_published_dar_template = !$teamPublishedDARTemplates->isEmpty();

        return $dataset;
    }

    /**
     * Return the full reconstructed metadata envelope for a specific version of
     * a dataset. The envelope matches the snapshot shape:
     *   { gwdmVersion, metadata: {...GWDM...}, original_metadata: {...} }
     *
     * Returns null when the requested version does not exist.
     */
    public function getVersion(Dataset $dataset, int $version): ?array
    {
        $exists = DatasetVersion::where('dataset_id', $dataset->id)
            ->where('version', $version)
            ->exists();

        if (!$exists) {
            return null;
        }

        return $this->getReconstructedMetadataEnvelope($dataset->id, $version);
    }

    /**
     * Return a lightweight version index for a dataset: id, version number,
     * title, short_title, created_at — no metadata payloads.
     */
    public function listVersions(Dataset $dataset): Collection
    {
        return DatasetVersion::where('dataset_id', $dataset->id)
            ->select(['id', 'version', 'title', 'short_title', 'created_at'])
            ->orderBy('version')
            ->get();
    }

    /**
     * Create a new dataset, translating metadata via MMC/TRASER.
     *
     * Returns ['translated' => true,  'dataset_id' => int, 'version_id' => int]
     *      or ['translated' => false, 'response'   => array] on translation failure.
     */
    public function create(
        array $input,
        Team $team,
        ?string $inputSchema,
        ?string $inputVersion,
        bool $elasticIndexing
    ): array {
        $input['metadata'] = $this->extractMetadata($input['metadata']);

        $payload            = $input['metadata'];
        $payload['extra']   = [
            'id'            => 'placeholder',
            'pid'           => 'placeholder',
            'datasetType'   => 'Health and disease',
            'publisherId'   => $team->pid,
            'publisherName' => $team->name,
        ];

        $isDraft        = $input['status'] === Dataset::STATUS_DRAFT;
        $traserResponse = MMC::translateDataModelType(
            json_encode($payload),
            Config::get('metadata.GWDM.name'),
            Config::get('metadata.GWDM.version'),
            $inputSchema,
            $inputVersion,
            !$isDraft,
            !$isDraft,
        );

        if (!$traserResponse['wasTranslated']) {
            return ['translated' => false, 'response' => $traserResponse];
        }

        $input['metadata']['original_metadata'] = $input['metadata']['metadata'];
        $input['metadata']['metadata']           = $traserResponse['metadata'];

        $pid               = $input['pid'] ?? (string) Str::uuid();
        $isCohortDiscovery = $input['is_cohort_discovery'] ?? false;

        $dataset = MMC::createDataset([
            'user_id'             => $input['user_id'],
            'team_id'             => $input['team_id'],
            'mongo_object_id'     => $input['mongo_object_id'] ?? null,
            'mongo_id'            => $input['mongo_id'] ?? null,
            'mongo_pid'           => $input['mongo_pid'] ?? null,
            'datasetid'           => $input['datasetid'] ?? null,
            'created'             => now(),
            'updated'             => now(),
            'submitted'           => now(),
            'pid'                 => $pid,
            'create_origin'       => $input['create_origin'],
            'status'              => $input['status'],
            'is_cohort_discovery' => $isCohortDiscovery,
        ]);

        $required = $this->buildRequiredBlock($dataset, 1);

        if (version_compare(Config::get('metadata.GWDM.version'), '1.1', '<')) {
            $input['metadata']['metadata']['summary']['publisher'] = [
                'publisherId'   => $team->pid,
                'publisherName' => $team->name,
            ];
        } else {
            $required['version'] = $input['metadata']['metadata']['required']['version']
                ?? $this->formatVersion(1);
        }

        $input['metadata']['metadata']['required'] = $required;
        $input['metadata']['gwdmVersion']          = Config::get('metadata.GWDM.version');

        [$title, $shortTitle] = $this->extractTitleFields($input['metadata']['metadata']);

        $version = MMC::createDatasetVersion([
            'dataset_id'  => $dataset->id,
            'metadata'    => json_encode($input['metadata']),
            'version'     => 1,
            // Base snapshot — no patch; title/short_title populated explicitly now
            // that the columns are no longer GENERATED (see migration 2026_03_11_133601).
            'patch'       => null,
            'title'       => $title,
            'short_title' => $shortTitle,
        ]);

        $this->mapCoverage($input['metadata'], $version);
        $this->dispatchJobs($dataset, $version->id, $input['metadata']['metadata'], $elasticIndexing, $input['status']);

        return [
            'translated' => true,
            'dataset_id' => $dataset->id,
            'version_id' => $version->id,
        ];
    }

    /**
     * Update an existing dataset with a new metadata version via TRASER.
     *
     * Each call — regardless of dataset status — creates a new DatasetVersion
     * record, preserving a complete history of changes. Callers can therefore
     * revert to any prior version via GET /datasets/{id}/version/{version}.
     *
     * Returns the previous version number (used in the audit description).
     *
     * @throws \RuntimeException when MMC translation fails
     */
    public function update(
        Dataset $dataset,
        array $input,
        int $userId,
        int $teamId,
        string $createOrigin,
        bool $elasticIndexing,
        Team $team,
    ): int {
        $payload = $this->extractMetadata($input['metadata']);
        $payload['extra'] = [
            'id'            => $dataset->id,
            'pid'           => $dataset->pid,
            'datasetType'   => 'Health and disease',
            'publisherId'   => $team->pid,
            'publisherName' => $team->name,
        ];

        $inputSchema       = $input['metadata']['schemaModel'] ?? null;
        $inputVersion      = $input['metadata']['schemaVersion'] ?? null;
        $submittedMetadata = $input['metadata'];
        $isDraft           = $input['status'] === Dataset::STATUS_DRAFT;

        $traserResponse = MMC::translateDataModelType(
            json_encode($payload),
            Config::get('metadata.GWDM.name'),
            Config::get('metadata.GWDM.version'),
            $inputSchema,
            $inputVersion,
            !$isDraft,
            !$isDraft,
        );

        if (!$traserResponse['wasTranslated']) {
            throw new \RuntimeException('metadata is in an unknown format and cannot be processed');
        }

        $versionNumber = $dataset->lastMetadataVersionNumber()->version;

        // Rebuild required.revisions to include all existing versions plus the
        // new one that is about to be written. This fixes a pre-existing gap
        // where the revisions array was only ever set on creation (version 1).
        $newVersionNumber = $versionNumber + 1;
        $traserResponse['metadata']['required'] = array_merge(
            $traserResponse['metadata']['required'] ?? [],
            $this->buildRequiredBlock($dataset, $newVersionNumber)
        );

        $dataset->update([
            'user_id'             => $userId,
            'team_id'             => $teamId,
            'updated'             => now(),
            'pid'                 => $dataset->pid,
            'create_origin'       => $createOrigin,
            'status'              => $input['status'],
            'is_cohort_discovery' => $input['is_cohort_discovery'] ?? false,
        ]);

        $datasetVersionId = $this->persistMetadataVersion(
            $dataset,
            $traserResponse['metadata'],
            $submittedMetadata,
            $versionNumber,
        );

        // TODO - Needs investigation as elastic indexing is handled within Observers
        // and potentially duplicated here. Ideally, the observers would spawn
        // jobs for indexing to reduce synchronous network blocking.
        // $this->dispatchJobs($dataset, $datasetVersionId, $submittedMetadata, $elasticIndexing, $input['status']);

        return $newVersionNumber;
    }

    /**
     * V2 (legacy) update — overwrites the existing version row in place.
     *
     * Preserved for backwards compatibility with third-party integrations that
     * rely on the v2 API. New integrations should use the v3 endpoint which
     * stores RFC 6902 delta patches and creates an immutable version history.
     *
     * @throws \RuntimeException when MMC translation fails
     */
    public function updateV2(
        Dataset $dataset,
        array $input,
        int $userId,
        int $teamId,
        string $createOrigin,
        bool $elasticIndexing,
        Team $team,
    ): int {
        $payload          = $this->extractMetadata($input['metadata']);
        $payload['extra'] = [
            'id'            => $dataset->id,
            'pid'           => $dataset->pid,
            'datasetType'   => 'Health and disease',
            'publisherId'   => $team->pid,
            'publisherName' => $team->name,
        ];

        $inputSchema       = $input['metadata']['schemaModel'] ?? null;
        $inputVersion      = $input['metadata']['schemaVersion'] ?? null;
        $submittedMetadata = $input['metadata']['metadata'];
        $isDraft           = $input['status'] === Dataset::STATUS_DRAFT;

        $traserResponse = MMC::translateDataModelType(
            json_encode($payload),
            Config::get('metadata.GWDM.name'),
            Config::get('metadata.GWDM.version'),
            $inputSchema,
            $inputVersion,
            !$isDraft,
            !$isDraft,
        );

        if (!$traserResponse['wasTranslated']) {
            throw new \RuntimeException('metadata is in an unknown format and cannot be processed');
        }

        $versionNumber = $dataset->lastMetadataVersionNumber()->version;

        $traserResponse['metadata']['required'] = array_merge(
            $traserResponse['metadata']['required'] ?? [],
            $this->buildRequiredBlock($dataset, $versionNumber)
        );

        $dataset->update([
            'user_id'             => $userId,
            'team_id'             => $teamId,
            'updated'             => now(),
            'pid'                 => $dataset->pid,
            'create_origin'       => $createOrigin,
            'status'              => $input['status'],
            'is_cohort_discovery' => $input['is_cohort_discovery'] ?? false,
        ]);

        $datasetVersionId = $this->persistMetadataVersionLegacy(
            $dataset,
            $traserResponse['metadata'],
            $submittedMetadata,
            $versionNumber,
        );

        $this->dispatchJobs($dataset, $datasetVersionId, $submittedMetadata, $elasticIndexing, $input['status']);

        return $versionNumber;
    }

    /**
     * Patch a dataset's status only. Dispatches LinkageExtraction if active.
     */
    public function patch(Dataset $dataset, string $status): void
    {
        $dataset->status = $status;
        $dataset->save();

        if ($status === Dataset::STATUS_ACTIVE) {
            $metadata = DatasetVersion::where('dataset_id', $dataset->id)->latest()->first();
            LinkageExtraction::dispatch($dataset->id, $metadata->id);
        }
    }

    /**
     * Update only the is_cohort_discovery flag (must be ACTIVE dataset).
     *
     * @throws \RuntimeException when dataset is not ACTIVE
     */
    public function updateCohortDiscovery(Dataset $dataset, bool $isCohortDiscovery): void
    {
        if ($dataset->status !== Dataset::STATUS_ACTIVE) {
            throw new \RuntimeException('Dataset status is ' . strtoupper($dataset->status));
        }

        $dataset->is_cohort_discovery = $isCohortDiscovery;
        $dataset->save();
    }

    public function delete(int $id): void
    {
        MMC::deleteDataset($id);
    }

    /**
     * Resolve dataset linkages by merging gateway-tracked relations with
     * any free-text linkages stored in the metadata's linkage.datasetLinkage field.
     *
     * Only returns linkages whose target dataset is ACTIVE.
     */
    public function getLinkages(int $datasetVersionId): array
    {
        $datasetLinkages = DatasetVersionHasDatasetVersion::query()
            ->where('dataset_version_has_dataset_version.dataset_version_source_id', $datasetVersionId)
            ->join('dataset_versions', 'dataset_versions.id', '=', 'dataset_version_has_dataset_version.dataset_version_target_id')
            ->join('datasets', 'datasets.id', '=', 'dataset_versions.dataset_id')
            ->where('datasets.status', Dataset::STATUS_ACTIVE)
            ->select([
                'dataset_version_has_dataset_version.linkage_type',
                'dataset_versions.short_title',
                'datasets.id as target_dataset_id',
            ])
            ->get()
            ->map(fn ($row) => [
                'title'        => $row->short_title,
                'url'          => config('gateway.gateway_url') . '/en/dataset/' . $row->target_dataset_id,
                'dataset_id'   => $row->target_dataset_id,
                'linkage_type' => $row->linkage_type,
            ])
            ->values()
            ->toArray();

        $metadataLinkage = DatasetVersion::where('id', $datasetVersionId)
            ->select('metadata')
            ->first()['metadata']['metadata']['linkage']['datasetLinkage'] ?? [];
        $allTitles       = [];

        foreach ($metadataLinkage as $linkageType => $link) {
            if ($link && is_array($link)) {
                foreach ($link as $l) {
                    $allTitles[] = ['title' => $l['title'], 'linkage_type' => $linkageType];
                }
            }
        }

        $gatewayTitles = array_column($datasetLinkages, 'title');
        foreach ($allTitles as $title) {
            if ($title['title'] && !in_array($title['title'], $gatewayTitles)) {
                $datasetLinkages[] = [
                    'title'        => $title['title'],
                    'url'          => null,
                    'dataset_id'   => null,
                    'linkage_type' => $title['linkage_type'],
                ];
            }
        }

        return $datasetLinkages;
    }

    /**
     * V2 (legacy) version persistence — overwrites the existing version row.
     * Mirrors MetadataVersioning@updateMetadataVersion for the service layer.
     */
    private function persistMetadataVersionLegacy(
        Dataset $dataset,
        array $newMetadata,
        array $previousMetadata,
        int $versionNumber,
    ): int {
        $metadataSaveObject = [
            'gwdmVersion'       => Config::get('metadata.GWDM.version'),
            'metadata'          => $newMetadata,
            'original_metadata' => $previousMetadata,
        ];

        [$title, $shortTitle] = $this->extractTitleFields($newMetadata);

        $dv = DatasetVersion::where([
            'dataset_id' => $dataset->id,
            'version'    => $versionNumber,
        ])->first();

        $dv->metadata    = json_encode($metadataSaveObject);
        $dv->title       = $title;
        $dv->short_title = $shortTitle;
        $dv->save();

        return $dv->id;
    }

    /**
     * Reconstruct the full GWDM metadata object for any version of a dataset.
     *
     * Algorithm:
     *  1. Find the nearest full snapshot at or below $targetVersion
     *     (identified by patch IS NULL — both the base v1 and every SNAPSHOT_INTERVAL
     *     version are snapshots).
     *  2. Apply each subsequent delta patch in ascending version order up to
     *     and including $targetVersion.
     *
     * The worst-case forward walk is (SNAPSHOT_INTERVAL - 1) delta applications.
     *
     * @return array  The GWDM metadata object (i.e. the value of metadata.metadata
     *                in a full snapshot row).
     *
     * @throws \RuntimeException when no base snapshot can be found.
     */
    private function reconstructGwdmMetadata(int $datasetId, int $targetVersion): array
    {
        // Step 1 — nearest snapshot at or below targetVersion.
        $snapshot = DatasetVersion::where('dataset_id', $datasetId)
            ->where('version', '<=', $targetVersion)
            ->whereNull('patch')
            ->orderBy('version', 'desc')
            ->first();

        if (!$snapshot) {
            throw new \RuntimeException(
                "No base snapshot found for dataset {$datasetId} at or before version {$targetVersion}."
            );
        }

        $gwdm = $snapshot->metadata['metadata'];

        if ($snapshot->version === $targetVersion) {
            return $gwdm;
        }

        // Step 2 — apply deltas from (snapshot.version + 1) to targetVersion.
        $deltas = DatasetVersion::where('dataset_id', $datasetId)
            ->where('version', '>', $snapshot->version)
            ->where('version', '<=', $targetVersion)
            ->whereNotNull('patch')
            ->orderBy('version')
            ->get(['version', 'patch']);

        // swaggest/json-diff works with stdClass graphs, not PHP arrays.
        // JsonPatch::apply() modifies the document in place (no return value).
        $current = json_decode(json_encode($gwdm));

        foreach ($deltas as $delta) {
            $patch = JsonPatch::import(json_decode(json_encode($delta->patch)));
            $patch->apply($current);
        }

        return json_decode(json_encode($current), true);
    }

    /**
     * Build the full metadata envelope ({gwdmVersion, metadata, original_metadata})
     * for a given version, suitable for returning in API responses or passing to TRASER.
     */
    private function getReconstructedMetadataEnvelope(int $datasetId, int $targetVersion): array
    {
        // We always need the row itself for gwdmVersion and original_metadata.
        $row = DatasetVersion::where('dataset_id', $datasetId)
            ->where('version', $targetVersion)
            ->first(['metadata', 'patch']);

        $gwdm = $this->reconstructGwdmMetadata($datasetId, $targetVersion);

        return [
            'gwdmVersion'       => $row->metadata['gwdmVersion'] ?? Config::get('metadata.GWDM.version'),
            'metadata'          => $gwdm,
            'original_metadata' => $row->metadata['original_metadata'] ?? [],
        ];
    }

    /**
     * Compute an RFC 6902 JSON Patch between two GWDM metadata arrays.
     *
     * REARRANGE_ARRAYS prevents the diff engine from emitting a full array
     * replacement when items are merely reordered — keeping patches surgical.
     *
     * @return array  A JSON-serialisable RFC 6902 patch array.
     */
    private function computePatch(array $from, array $to): array
    {
        $fromObj = json_decode(json_encode($from));
        $toObj   = json_decode(json_encode($to));

        $diff = new JsonDiff($fromObj, $toObj, JsonDiff::REARRANGE_ARRAYS);

        // JsonPatch implements JsonSerializable; encode then decode to get a
        // plain PHP array suitable for json_encode() storage in the patch column.
        return json_decode(json_encode($diff->getPatch()), true) ?? [];
    }

    /**
     * Write a new DatasetVersion row for an update, choosing between a delta
     * and a materialised snapshot based on SNAPSHOT_INTERVAL.
     *
     * Delta row    : stores only a reduced metadata envelope + the RFC 6902 patch describing
     *                 the change from the previous version.
     * Snapshot row : stores the COMPLETE reconstructed GWDM state at that version (patch = null).
     *                 This is NOT a rollup of accumulated patches — it is the fully-materialised
     *                 metadata object, identical in shape to v1. Storing the final state (rather
     *                 than a diff) means reconstruction can start here with zero patch applications,
     *                 capping the forward-walk cost at ≤ (SNAPSHOT_INTERVAL - 1) deltas for any
     *                 version in the next window.
     *
     * @return int  The ID of the newly created DatasetVersion row.
     */
    private function persistMetadataVersion(
        Dataset $dataset,
        array $newGwdmMetadata,
        array $previousMetadata,
        int $versionNumber,
    ): int {
        $newVersionNumber = $versionNumber + 1;

        [$title, $shortTitle] = $this->extractTitleFields($newGwdmMetadata);

        $isSnapshot = ($newVersionNumber % self::SNAPSHOT_INTERVAL === 0);

        if ($isSnapshot) {
            // For every SNAPSHOT_INTERVAL version we write a full snapshot so that
            // reconstruction never has to walk more than (SNAPSHOT_INTERVAL - 1)
            // deltas. The shape is identical to the base (v1) row.
            $envelope = [
                'gwdmVersion'       => Config::get('metadata.GWDM.version'),
                'metadata'          => $newGwdmMetadata,
                'original_metadata' => $previousMetadata,
            ];

            $dv = DatasetVersion::create([
                'dataset_id'  => $dataset->id,
                'metadata'    => json_encode($envelope),
                // Snapshot version, thus full rebuild of metadata and no patch.
                'patch'       => null,
                'version'     => $newVersionNumber,
                'title'       => $title,
                'short_title' => $shortTitle,
            ]);
        } else {
            // Delta row: reconstruct the current full GWDM object, diff against
            // the new metadata to produce a minimal RFC 6902 patch.
            $currentGwdm = $this->reconstructGwdmMetadata($dataset->id, $versionNumber);
            $patch       = $this->computePatch($currentGwdm, $newGwdmMetadata);

            $dv = DatasetVersion::create([
                'dataset_id'  => $dataset->id,
                // Patch delta, therefore no metadata stored.
                'metadata'    => [],
                'patch'       => $patch,   // array — the cast handles JSON encoding
                'version'     => $newVersionNumber,
                'title'       => $title,
                'short_title' => $shortTitle,
            ]);
        }

        // -----------------------------------------------------------------------
        // Relation pivot table updates — DISCUSSION POINT FOR REVIEW
        // -----------------------------------------------------------------------
        // When a new version is persisted (delta or snapshot) the spatial coverage
        // pivot table (dataset_version_has_spatial_coverage) should be updated
        // to reflect the new version's coverage values.
        //
        // Currently mapCoverage() is only called during create(), which means
        // existing version rows correctly reflect coverage at v1 but subsequent
        // version rows have no pivot entries. This gap was pre-existing before
        // delta versioning was introduced, but becomes more visible now that
        // updates genuinely create new rows.
        //
        // The call below addresses spatial coverage. Questions for review:
        //
        //  1. Should the call use the FULL new metadata or only the GWDM portion?
        //     mapCoverage() expects the shape { metadata: {...GWDM...} }, so we
        //     reconstruct a compatible envelope here.
        //
        //  2. Should the old version's coverage rows be soft-deleted (or at least
        //     flagged) when a new version supersedes them? At present coverage rows
        //     accumulate across versions, which is used intentionally by
        //     Dataset::getAllSpatialCoveragesAttribute() to union coverage across
        //     all version rows. Whether that union is still desirable now that each
        //     version has its own row is worth discussing.
        //
        //  3. Named entities (dataset_version_has_named_entities) are populated
        //     asynchronously by the TermExtraction job and do NOT need to be
        //     handled here — the job already receives the new version ID via
        //     dispatchJobs() above.
        //
        //  4. Tool linkages (dataset_version_has_tool) are managed externally
        //     (not set during a metadata update) and are therefore intentionally
        //     omitted here.
        //
        // TODO: revisit points 2 and 3 in a follow-up ticket once the overall
        //       per-version vs. cross-version model is confirmed.
        // -----------------------------------------------------------------------
        $envelopeForCoverage = [
            'metadata' => $newGwdmMetadata,
        ];
        $this->mapCoverage($envelopeForCoverage, $dv);

        return $dv->id;
    }

    /**
     * Centralises TermExtraction + LinkageExtraction dispatch.
     *
     * Previously duplicated in:
     *   - MetadataOnboard@metadataOnboard
     *   - DatasetController@update (V2)
     *   - DatasetController@edit   (V2)
     */
    private function dispatchJobs(
        Dataset $dataset,
        int $versionId,
        array $metadata,
        bool $elasticIndexing,
        string $status
    ): void {
        if ($status !== Dataset::STATUS_ACTIVE) {
            return;
        }

        LinkageExtraction::dispatch($dataset->id, $versionId);

        if (Config::get('ted.enabled')) {
            $tedData = Config::get('ted.use_partial') ? $metadata['summary'] : $metadata;
            TermExtraction::dispatch(
                $dataset->id,
                $versionId,
                '1',
                base64_encode(gzcompress(gzencode(json_encode($tedData), 6))),
                $elasticIndexing,
                Config::get('ted.use_partial')
            );
        }
    }

    /**
     * Normalise incoming metadata into a consistent nested shape before
     * passing to TRASER.
     *
     * Mirrors DatasetsV2Helpers@extractMetadata (kept for TeamDatasetController).
     */
    private function extractMetadata(mixed $metadata): array
    {
        if (is_array($metadata) && Arr::has($metadata, 'metadata.metadata')) {
            $metadata = $metadata['metadata'];
        } elseif (is_array($metadata) && !Arr::has($metadata, 'metadata')) {
            $metadata = ['metadata' => $metadata];
        }

        if (is_string($metadata) && isJsonString($metadata)) {
            $metadata = json_decode($metadata, true);
        }

        if (
            isset($metadata['metadata']) &&
            is_string($metadata['metadata']) &&
            isJsonString($metadata['metadata'])
        ) {
            $tmp['metadata'] = json_decode($metadata['metadata'], true);
            $metadata = $tmp;
        }

        return $metadata;
    }

    /**
     * Build the GWDM required block for a given version, including the full
     * revisions history up to and including $newVersionNumber.
     *
     * On create() this produces a single-entry revisions array (v1 only).
     * On update() this is called after incrementing so $newVersionNumber already
     * reflects the version about to be written; all prior versions are fetched
     * from the DB and prepended.
     */
    private function buildRequiredBlock(Dataset $dataset, int $newVersionNumber): array
    {
        // Fetch all version numbers already persisted for this dataset (excluding
        // the new version which has not yet been written).
        $existingVersions = DatasetVersion::where('dataset_id', $dataset->id)
            ->orderBy('version')
            ->pluck('version')
            ->toArray();

        // Build a revision entry for every version including the new one.
        $allVersionNumbers = array_merge($existingVersions, [$newVersionNumber]);
        $allVersionNumbers = array_unique($allVersionNumbers);
        sort($allVersionNumbers);

        $revisions = array_map(fn ($v) => [
            'url'     => config('gateway.gateway_url') . '/dataset/' . $dataset->id . '?version=' . $this->formatVersion($v),
            'version' => $this->formatVersion($v),
        ], $allVersionNumbers);

        return [
            'gatewayId'  => strval($dataset->id),
            'gatewayPid' => $dataset->pid,
            'issued'     => $dataset->created,
            'modified'   => $dataset->updated,
            'revisions'  => $revisions,
        ];
    }

    private function formatVersion(int $version): string
    {
        return "{$version}.0.0";
    }

    /**
     * Extract title and shortTitle from a GWDM metadata array.
     *
     * Returns [$title, $shortTitle] — either may be null if not present.
     * Used to populate the now-regular (non-generated) title/short_title columns.
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function extractTitleFields(array $gwdmMetadata): array
    {
        $title      = $gwdmMetadata['summary']['title'] ?? null;
        $shortTitle = $gwdmMetadata['summary']['shortTitle'] ?? $title;

        return [$title, $shortTitle];
    }

    /**
     * Map metadata coverage/spatial string to the SpatialCoverage controlled list.
     * Mirrors MetadataOnboard@mapCoverage (kept intact for V1).
     */
    private function mapCoverage(array $metadata, DatasetVersion $version): void
    {
        if (!isset($metadata['metadata']['coverage']['spatial'])) {
            return;
        }

        $coverage     = strtolower($metadata['metadata']['coverage']['spatial']);
        $allCoverages = SpatialCoverage::all();
        $ukCoverages  = $allCoverages->filter(fn ($c) => $c->region !== 'Rest of the world');
        $worldId      = $allCoverages->firstWhere('region', 'Rest of the world')?->id;
        $matchFound   = false;

        foreach ($ukCoverages as $c) {
            if (str_contains($coverage, strtolower($c->region))) {
                DatasetVersionHasSpatialCoverage::updateOrCreate([
                    'dataset_version_id'  => (int) $version->id,
                    'spatial_coverage_id' => (int) $c->id,
                ]);
                $matchFound = true;
            }
        }

        if (!$matchFound) {
            if (str_contains($coverage, 'united kingdom')) {
                foreach ($ukCoverages as $c) {
                    DatasetVersionHasSpatialCoverage::updateOrCreate([
                        'dataset_version_id'  => (int) $version->id,
                        'spatial_coverage_id' => (int) $c->id,
                    ]);
                }
            } else {
                DatasetVersionHasSpatialCoverage::updateOrCreate([
                    'dataset_version_id'  => (int) $version->id,
                    'spatial_coverage_id' => (int) $worldId,
                ]);
            }
        }
    }

    /**
     * Raw SQL count of active DURs linked to a dataset version.
     * Mirrors ModelHelpers@countActiveDursForDatasetVersion.
     * Pre-computed by the service so the Resource runs no queries.
     */
    private function countActiveDurs(int $datasetVersionId): ?int
    {
        $result = DB::select('
            SELECT COUNT(dur_has_dataset_version.id) AS count
            FROM dur_has_dataset_version
            INNER JOIN dur ON dur.id = dur_has_dataset_version.dur_id
            WHERE dur_has_dataset_version.dataset_version_id = :dataset_version_id
              AND dur_has_dataset_version.deleted_at IS NULL
              AND dur.status = \'ACTIVE\'
        ', ['dataset_version_id' => $datasetVersionId]);

        return $result[0]->count ?? null;
    }

    /**
     * Raw SQL count of active Publications linked to a dataset version.
     * Mirrors ModelHelpers@countActivePublicationsForDatasetVersion.
     */
    private function countActivePublications(int $datasetVersionId): ?int
    {
        $result = DB::select('
            SELECT COUNT(publication_has_dataset_version.id) AS count
            FROM publication_has_dataset_version
            INNER JOIN publications ON publications.id = publication_has_dataset_version.publication_id
            WHERE publication_has_dataset_version.dataset_version_id = :dataset_version_id
              AND publication_has_dataset_version.deleted_at IS NULL
              AND publications.status = \'ACTIVE\'
        ', ['dataset_version_id' => $datasetVersionId]);

        return $result[0]->count ?? null;
    }
}
