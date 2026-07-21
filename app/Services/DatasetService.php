<?php

namespace App\Services;

use App\Context\GwdmVersionContext;
use App\Jobs\LinkageExtraction;
use App\Jobs\TermExtraction;
use App\Models\DataAccessTemplate;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasSpatialCoverage;
use App\Models\SpatialCoverage;
use App\Models\Team;
use App\Services\Gwdm\GwdmHandlerFactory;
use Config;
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

    public function __construct(
        private readonly GwdmVersionContext $gwdmVersionContext,
        private readonly GwdmHandlerFactory $handlerFactory,
    ) {}

    public function list(
        ?string $filterStatus,
        ?string $filterTitle,
        bool $withMetadata,
        int $perPage,
        ?string $partnerContext = null,
    ): LengthAwarePaginator {
        if (! empty($filterTitle)) {
            // Use a subquery for the status filter to avoid materialising all IDs.
            // The unique-per-dataset deduplication still requires PHP-side processing
            // because MySQL lacks a portable "latest version per group" without window
            // functions, so we load only the filtered version rows and deduplicate.
            $statusSubquery = Dataset::query()
                ->when($filterStatus, fn ($q) => $q->where('status', $filterStatus))
                ->when(
                    $partnerContext && ($partnerContext !== 'HDRUK' || ! config('partners.allow_cross_context_read', true)),
                    fn ($q) => $q->where('partner_context', $partnerContext)
                )
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
                ->when($filterStatus, fn ($q) => $q->where('status', $filterStatus))
                ->when(
                    $partnerContext && ($partnerContext !== 'HDRUK' || ! config('partners.allow_cross_context_read', true)),
                    fn ($q) => $q->where('partner_context', $partnerContext)
                );
        }

        if ($withMetadata) {
            $query->with('latestMetadata');
        }

        return $query->applySorting()->paginate($perPage, ['*'], 'page');
    }

    public function findActive(int $id, ?string $partnerContext = null): ?Dataset
    {
        return Dataset::with('team')
            ->where('status', Dataset::STATUS_ACTIVE)
            ->when(
                $partnerContext && $partnerContext !== 'HDRUK',
                fn ($q) => $q->where('partner_context', $partnerContext)
            )
            ->find($id);
    }

    /**
     * Resolve all attributes required for a detailed show response.
     *
     * Replaces DatasetsV2Helpers@getDatasetDetails. All counts are pre-computed
     * here so the Resource's toArray() runs no queries.
     *
     * @throws \InvalidArgumentException when schema_model/schema_version are mismatched
     * @throws \RuntimeException when MMC translation fails
     */
    public function prepareForShow(
        Dataset $dataset,
        ?string $outputSchemaModel = null,
        ?string $outputSchemaVersion = null,
    ): Dataset|string {
        $dataset->loadMissing('team');

        $requestedGwdmVersion = $this->gwdmVersionContext->requestedVersion();

        // Single query replaces latestVersionID() + versions()->pluck('id') — two
        // separate dataset_versions round-trips in the original code.
        $allVersions = $dataset->versions()
            ->select(['id', 'version', 'gwdm_version'])
            ->orderBy('version', 'desc')
            ->get();

        // Cache version IDs so each allActive* accessor reuses this result instead
        // of re-querying the same versions table independently.
        $dataset->cachedVersionIds = $allVersions->pluck('id')->toArray();

        if ($requestedGwdmVersion !== null) {
            $latestVersionRow = $allVersions->firstWhere('gwdm_version', $requestedGwdmVersion);
            if ($latestVersionRow === null) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                    "No dataset version found with GWDM version '{$requestedGwdmVersion}'."
                );
            }
            $latestVersionId = $latestVersionRow->id;
        } else {
            $latestVersionId = $allVersions->first()?->id;
        }

        $activeCollections = $dataset->allActiveCollections ?? [];
        $activeDurs = $dataset->allActiveDurs ?? [];
        $activePublications = $dataset->allActivePublications ?? [];

        $dataset->durs_count = count(array_filter(
            $activeDurs,
            fn ($d) => in_array($latestVersionId, $d['dataset_version_ids'] ?? [])
        ));
        $dataset->publications_count = count(array_filter(
            $activePublications,
            fn ($p) => in_array($latestVersionId, array_column($p['dataset_versions'] ?? [], 'dataset_version_id'))
        ));
        $dataset->tools_count = count($dataset->allActiveTools);
        $dataset->collections_count = count($activeCollections);
        $dataset->spatialCoverage = $dataset->allSpatialCoverages ?? [];
        $dataset->durs = $activeDurs;
        $dataset->publications = $activePublications;
        $dataset->named_entities = $dataset->allNamedEntities ?? [];
        $dataset->collections = $activeCollections;

        if ($outputSchemaModel && ! $outputSchemaVersion) {
            throw new \InvalidArgumentException('schema_model provided without schema_version');
        }
        if ($outputSchemaVersion && ! $outputSchemaModel) {
            throw new \InvalidArgumentException('schema_version provided without schema_model');
        }

        $translateRequested = $outputSchemaModel && $outputSchemaVersion;

        // Reduced relation set is sufficient for the TRASER path (metadata is replaced
        // entirely by the translation result). No-translation path needs the full graph.
        $loadRelation = $translateRequested ? 'reducedLinkedDatasetVersions' : 'linkedDatasetVersions';

        $withLinks = DatasetVersion::where('id', $latestVersionId)
            ->with([$loadRelation])
            ->first();

        if ($withLinks) {
            // Always reconstruct via the handler system. The handler encapsulates the
            // storage strategy: 2.x reads from the JSON blob; 3.0 reads from SQL tables.
            // Validation runs only when TRASER translation is requested so that TRASER
            // is not a hard dependency for every read.
            $envelope = $this->getReconstructedMetadataEnvelope(
                $dataset->id,
                $withLinks->version,
                validate: $translateRequested,
                prefetched: $withLinks,
            );

            if ($translateRequested) {
                $translated = MMC::translateDataModelType(
                    json_encode($envelope),
                    $outputSchemaModel,
                    $outputSchemaVersion,
                    Config::get('metadata.GWDM.name'),
                    $envelope['gwdmVersion'],
                );

                if (! $translated['wasTranslated']) {
                    $traserError = is_array($translated['traser_message'])
                        ? json_encode($translated['traser_message'])
                        : ($translated['traser_message'] ?? 'unknown error');
                    throw new \RuntimeException($traserError);
                }

                $withLinks->metadata = ['metadata' => $translated['metadata']];
            } else {
                $withLinks->metadata = $envelope;
            }

            $dataset->setRelation('versions', collect([$withLinks]));
        }

        if ($latestVersionId !== null) {
            $latestGwdmVersion = $allVersions->firstWhere('id', $latestVersionId)?->gwdm_version
                ?? $this->gwdmVersionContext->targetVersion();
            $dataset->linkages = $this->handlerFactory->resolve($latestGwdmVersion)->getLinkages($latestVersionId);
        } else {
            $dataset->linkages = [];
        }

        $dataset->team->has_published_dar_template = DataAccessTemplate::where('team_id', $dataset->team->id)
            ->where('published', 1)
            ->exists();

        return $dataset;
    }

    /**
     * Fast path for ?view=metadataOnly — skips all relation loading (DURs, tools,
     * collections, publications, named entities, linkages, team DAR check) and
     * returns only the reconstructed GWDM metadata envelope.
     *
     * Supports the same x-gwdm-version header and schema_model/schema_version
     * TRASER translation as the full show path.
     *
     * @return array{gwdmVersion?: string, metadata?: array} empty when the dataset has no version rows
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException when the requested GWDM version has no rows
     * @throws \InvalidArgumentException when schema_model/schema_version are mismatched
     * @throws \RuntimeException when TRASER translation fails
     */
    public function getMetadataOnly(
        Dataset $dataset,
        ?string $outputSchemaModel = null,
        ?string $outputSchemaVersion = null,
    ): array {
        if ($outputSchemaModel && ! $outputSchemaVersion) {
            throw new \InvalidArgumentException('schema_model provided without schema_version');
        }
        if ($outputSchemaVersion && ! $outputSchemaModel) {
            throw new \InvalidArgumentException('schema_version provided without schema_model');
        }

        $requestedGwdmVersion = $this->gwdmVersionContext->requestedVersion();

        $allVersions = $dataset->versions()
            ->select(['id', 'version', 'gwdm_version'])
            ->orderBy('version', 'desc')
            ->get();

        if ($requestedGwdmVersion !== null) {
            $row = $allVersions->firstWhere('gwdm_version', $requestedGwdmVersion);
            if ($row === null) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                    "No dataset version found with GWDM version '{$requestedGwdmVersion}'."
                );
            }
            $latestVersionId = $row->id;
        } else {
            $latestVersionId = $allVersions->first()?->id;
        }

        $translateRequested = $outputSchemaModel && $outputSchemaVersion;

        // Only need linked versions when translating via TRASER.
        $dv = $translateRequested
            ? DatasetVersion::where('id', $latestVersionId)->with(['reducedLinkedDatasetVersions'])->first()
            : DatasetVersion::find($latestVersionId);

        if (! $dv) {
            return [];
        }

        $envelope = $this->getReconstructedMetadataEnvelope(
            $dataset->id,
            $dv->version,
            validate: $translateRequested,
            prefetched: $dv,
        );

        if ($translateRequested) {
            $translated = MMC::translateDataModelType(
                json_encode($envelope),
                $outputSchemaModel,
                $outputSchemaVersion,
                Config::get('metadata.GWDM.name'),
                $envelope['gwdmVersion'],
            );

            if (! $translated['wasTranslated']) {
                $traserError = is_array($translated['traser_message'])
                    ? json_encode($translated['traser_message'])
                    : ($translated['traser_message'] ?? 'unknown error');
                throw new \RuntimeException($traserError);
            }

            return [
                'gwdmVersion' => $envelope['gwdmVersion'],
                'metadata' => $translated['metadata'],
            ];
        }

        return [
            'gwdmVersion' => $envelope['gwdmVersion'],
            'metadata' => $envelope['metadata'],
        ];
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

        if (! $exists) {
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

    public function getMetadataVersionSummary(Dataset $dataset): array
    {
        // countBy() over the plucked version strings avoids selecting a `count`
        // aggregate alias onto the Eloquent model (which phpstan cannot see as a
        // property). Keys stay in ascending gwdm_version order via the orderBy.
        $counts = DatasetVersion::where('dataset_id', $dataset->id)
            ->orderBy('gwdm_version')
            ->pluck('gwdm_version')
            ->countBy();

        return [
            'total_versions' => $counts->sum(),
            'gwdm_versions' => $counts->map(fn (int $count, string $version) => [
                'gwdm_version' => $version,
                'count' => $count,
            ])->values()->all(),
        ];
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
        bool $elasticIndexing,
        string $partnerContext = 'HDRUK',
    ): array {
        $input['metadata'] = $this->extractMetadata($input['metadata']);

        $payload = $input['metadata'];
        $payload['extra'] = [
            'id' => 'placeholder',
            'pid' => 'placeholder',
            'datasetType' => 'Health and disease',
            'publisherId' => $team->pid,
            'publisherName' => $team->name,
        ];

        $targetGwdmVersion = $this->gwdmVersionContext->targetVersion();
        $isDraft = $input['status'] === Dataset::STATUS_DRAFT;
        $traserResponse = MMC::translateDataModelType(
            json_encode($payload),
            Config::get('metadata.GWDM.name'),
            $targetGwdmVersion,
            $inputSchema,
            $inputVersion,
            ! $isDraft,
            ! $isDraft,
        );

        if (! $traserResponse['wasTranslated']) {
            return ['translated' => false, 'response' => $traserResponse];
        }

        $input['metadata']['original_metadata'] = $input['metadata']['metadata'];
        $input['metadata']['metadata'] = $traserResponse['metadata'];

        $pid = $input['pid'] ?? (string) Str::uuid();
        $isCohortDiscovery = $input['is_cohort_discovery'] ?? false;

        $dataset = MMC::createDataset([
            'user_id' => $input['user_id'],
            'team_id' => $input['team_id'],
            'mongo_object_id' => $input['mongo_object_id'] ?? null,
            'mongo_id' => $input['mongo_id'] ?? null,
            'mongo_pid' => $input['mongo_pid'] ?? null,
            'datasetid' => $input['datasetid'] ?? null,
            'created' => now(),
            'updated' => now(),
            'submitted' => now(),
            'pid' => $pid,
            'create_origin' => $input['create_origin'],
            'status' => $input['status'],
            'is_cohort_discovery' => $isCohortDiscovery,
            'partner_context' => $partnerContext,
        ]);

        $handler = $this->handlerFactory->resolve($targetGwdmVersion);
        $gwdm = $handler->prepareMetadata(
            $input['metadata']['metadata'],
            $dataset,
            $team,
            1,
        );
        $envelope = $handler->buildEnvelope($gwdm, $input['metadata']['original_metadata']);
        [$title, $shortTitle] = $handler->extractTitleFields($gwdm);

        // Create the base version row and its handler-owned structured tables
        // atomically so a GWDM 3.0 section-write failure cannot leave an orphaned
        // version with no metadata (see persistMetadataVersion for the same rule).
        $version = DB::transaction(function () use (
            $dataset,
            $envelope,
            $title,
            $shortTitle,
            $targetGwdmVersion,
            $handler,
            $gwdm,
        ) {
            $version = MMC::createDatasetVersion([
                'dataset_id' => $dataset->id,
                'metadata' => json_encode($envelope),
                'version' => 1,
                // Base snapshot — no patch; title/short_title populated explicitly now
                // that the columns are no longer GENERATED (see migration 2026_03_11_133601).
                'patch' => null,
                'title' => $title,
                'short_title' => $shortTitle,
                'gwdm_version' => $targetGwdmVersion,
            ]);

            $handler->afterStore($dataset, $version, $gwdm);

            return $version;
        });

        $this->mapCoverage(['metadata' => $gwdm], $version);
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
            'id' => $dataset->id,
            'pid' => $dataset->pid,
            'datasetType' => 'Health and disease',
            'publisherId' => $team->pid,
            'publisherName' => $team->name,
        ];

        $inputSchema = $input['metadata']['schemaModel'] ?? null;
        $inputVersion = $input['metadata']['schemaVersion'] ?? null;
        $submittedMetadata = $input['metadata'];
        $isDraft = $input['status'] === Dataset::STATUS_DRAFT;
        $targetGwdmVersion = $this->gwdmVersionContext->targetVersion();

        $traserResponse = MMC::translateDataModelType(
            json_encode($payload),
            Config::get('metadata.GWDM.name'),
            $targetGwdmVersion,
            $inputSchema,
            $inputVersion,
            ! $isDraft,
            ! $isDraft,
        );

        if (! $traserResponse['wasTranslated']) {
            throw new \RuntimeException('metadata is in an unknown format and cannot be processed');
        }

        $versionNumber = $dataset->lastMetadataVersionNumber()->version;
        $newVersionNumber = $versionNumber + 1;

        $handler = $this->handlerFactory->resolve($targetGwdmVersion);
        $gwdm = $handler->prepareMetadata(
            $traserResponse['metadata'],
            $dataset,
            $team,
            $newVersionNumber,
        );

        $dataset->update([
            'user_id' => $userId,
            'team_id' => $teamId,
            'updated' => now(),
            'pid' => $dataset->pid,
            'create_origin' => $createOrigin,
            'status' => $input['status'],
            'is_cohort_discovery' => $input['is_cohort_discovery'] ?? false,
        ]);

        $datasetVersionId = $this->persistMetadataVersion(
            $dataset,
            $gwdm,
            $submittedMetadata,
            $versionNumber,
        );

        // Dispatch LinkageExtraction + TermExtraction. These are complementary to
        // (not duplicated by) DatasetVersionObserver, which dispatches the Extract*
        // and indexing jobs on the "created" event — the observer never dispatches
        // linkage or term extraction. Pass the prepared GWDM object (not the input
        // wrapper) so the TED payload shape matches create() and updateV2().
        $this->dispatchJobs($dataset, $datasetVersionId, $gwdm, $elasticIndexing, $input['status']);

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
        $payload = $this->extractMetadata($input['metadata']);
        $payload['extra'] = [
            'id' => $dataset->id,
            'pid' => $dataset->pid,
            'datasetType' => 'Health and disease',
            'publisherId' => $team->pid,
            'publisherName' => $team->name,
        ];

        $inputSchema = $input['metadata']['schemaModel'] ?? null;
        $inputVersion = $input['metadata']['schemaVersion'] ?? null;
        $submittedMetadata = $input['metadata']['metadata'];
        $isDraft = $input['status'] === Dataset::STATUS_DRAFT;
        $targetGwdmVersion = $this->gwdmVersionContext->targetVersion();

        $traserResponse = MMC::translateDataModelType(
            json_encode($payload),
            Config::get('metadata.GWDM.name'),
            $targetGwdmVersion,
            $inputSchema,
            $inputVersion,
            ! $isDraft,
            ! $isDraft,
        );

        if (! $traserResponse['wasTranslated']) {
            throw new \RuntimeException('metadata is in an unknown format and cannot be processed');
        }

        $versionNumber = $dataset->lastMetadataVersionNumber()->version;

        $handler = $this->handlerFactory->resolve($targetGwdmVersion);
        $gwdm = $handler->prepareMetadata(
            $traserResponse['metadata'],
            $dataset,
            $team,
            $versionNumber,
        );

        $dataset->update([
            'user_id' => $userId,
            'team_id' => $teamId,
            'updated' => now(),
            'pid' => $dataset->pid,
            'create_origin' => $createOrigin,
            'status' => $input['status'],
            'is_cohort_discovery' => $input['is_cohort_discovery'] ?? false,
        ]);

        $datasetVersionId = $this->persistMetadataVersionLegacy(
            $dataset,
            $gwdm,
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
            throw new \RuntimeException('Dataset status is '.strtoupper($dataset->status));
        }

        $dataset->is_cohort_discovery = $isCohortDiscovery;
        $dataset->save();
    }

    public function delete(int $id): void
    {
        MMC::deleteDataset($id);
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
        $targetGwdmVersion = $this->gwdmVersionContext->targetVersion();
        $handler = $this->handlerFactory->resolve($targetGwdmVersion);

        $envelope = $handler->buildEnvelope($newMetadata, $previousMetadata);
        [$title, $shortTitle] = $handler->extractTitleFields($newMetadata);

        $dv = DatasetVersion::where([
            'dataset_id' => $dataset->id,
            'version' => $versionNumber,
        ])->first();

        // Overwrite the version row and rewrite its structured tables atomically
        // (see persistMetadataVersion for the same rationale).
        DB::transaction(function () use ($dv, $envelope, $title, $shortTitle, $targetGwdmVersion, $handler, $dataset, $newMetadata) {
            $dv->metadata = json_encode($envelope);
            $dv->title = $title;
            $dv->short_title = $shortTitle;
            $dv->gwdm_version = $targetGwdmVersion;
            $dv->save();

            $handler->afterStore($dataset, $dv, $newMetadata);
        });

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
     * @return array The GWDM metadata object (i.e. the value of metadata.metadata
     *               in a full snapshot row).
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

        if (! $snapshot) {
            throw new \RuntimeException(
                "No base snapshot found for dataset {$datasetId} at or before version {$targetVersion}."
            );
        }

        $storedData = (array) $snapshot->metadata;
        $gwdmVersion = $snapshot->gwdm_version
            ?? $storedData['gwdmVersion']
            ?? $this->gwdmVersionContext->targetVersion();

        $gwdm = $this->handlerFactory->resolve($gwdmVersion)->extractStoredGwdm($storedData);

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
    public function getReconstructedMetadataEnvelope(
        int $datasetId,
        int $targetVersion,
        bool $validate = true,
        ?DatasetVersion $prefetched = null,
        bool $applySupplementary = true,
    ): array {
        $row = $prefetched ?? DatasetVersion::where('dataset_id', $datasetId)
            ->where('version', $targetVersion)
            ->first();

        // Prefer the indexed column; fall back to JSON envelope; then context default.
        $storedVersion = $row->gwdm_version
            ?? $row->metadata['gwdmVersion']
            ?? $this->gwdmVersionContext->targetVersion();

        $handler = $this->handlerFactory->resolve($storedVersion);

        // Pre-populate section relations on $row so afterRead() can access them
        // without firing individual per-section queries. No-op for 2.x handlers.
        $handler->preloadSections($row);

        // When the prefetched version is itself a full snapshot (patch IS NULL),
        // extract the stored GWDM directly — skips the snapshot search query
        // inside reconstructGwdmMetadata on every snapshot row.
        if ($prefetched !== null && $prefetched->patch === null) {
            $gwdm = $handler->extractStoredGwdm((array) $prefetched->metadata);
        } else {
            $gwdm = $this->reconstructGwdmMetadata($datasetId, $targetVersion);
        }

        // For 3.0+, afterRead() merges supplementary data from structured SQL tables.
        // Skipped when the caller is itself the writer of those tables (e.g.
        // extractLinkages()), to avoid a circular read that would feed stale
        // junction-table data back into the very rows being rewritten.
        if ($applySupplementary) {
            $supplementary = $handler->afterRead($row);
            if (! empty($supplementary)) {
                $gwdm = array_merge($gwdm, $supplementary);
            }
        }

        // Validate the reconstructed metadata only when explicitly requested.
        // Data is validated at write time; re-validating on every read would make
        // TRASER a hard dependency for all dataset show requests.
        if ($validate) {
            $metadataJson = json_encode(['metadata' => $gwdm]);
            $isValid = MMC::validateDataModelType($metadataJson, Config::get('metadata.GWDM.name'), $storedVersion);
            if (! $isValid) {
                \Log::warning('GWDM read validation failed', [
                    'dataset_id' => $datasetId,
                    'version' => $targetVersion,
                    'gwdm_version' => $storedVersion,
                ]);
                throw new \RuntimeException("Reconstructed GWDM for version {$storedVersion} failed schema validation.");
            }
        }

        return [
            'gwdmVersion' => $storedVersion,
            'metadata' => $gwdm,
            'original_metadata' => $row->metadata['original_metadata'] ?? [],
        ];
    }

    /**
     * Compute an RFC 6902 JSON Patch between two GWDM metadata arrays.
     *
     * REARRANGE_ARRAYS prevents the diff engine from emitting a full array
     * replacement when items are merely reordered — keeping patches surgical.
     *
     * @return array A JSON-serialisable RFC 6902 patch array.
     */
    private function computePatch(array $from, array $to): array
    {
        $fromObj = json_decode(json_encode($from));
        $toObj = json_decode(json_encode($to));

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
     * @return int The ID of the newly created DatasetVersion row.
     */
    private function persistMetadataVersion(
        Dataset $dataset,
        array $newGwdmMetadata,
        array $previousMetadata,
        int $versionNumber,
    ): int {
        $newVersionNumber = $versionNumber + 1;
        $targetGwdmVersion = $this->gwdmVersionContext->targetVersion();
        $handler = $this->handlerFactory->resolve($targetGwdmVersion);

        [$title, $shortTitle] = $handler->extractTitleFields($newGwdmMetadata);

        // Force a full snapshot when the GWDM schema version changes from the
        // previous version to this one. A delta patch computed across differing
        // schemas would be meaningless — and reconstruction resolves the handler
        // from the nearest snapshot's gwdm_version — so every delta window must
        // remain schema-homogeneous. The backfill applies the same rule.
        $previousGwdmVersion = DatasetVersion::where('dataset_id', $dataset->id)
            ->where('version', $versionNumber)
            ->value('gwdm_version');
        $schemaChanged = $previousGwdmVersion !== null && $previousGwdmVersion !== $targetGwdmVersion;

        $isSnapshot = ($newVersionNumber % self::SNAPSHOT_INTERVAL === 0) || $schemaChanged;

        // Persist the version row and its handler-owned structured tables in a
        // single transaction. Without this, a failure inside afterStore() (e.g.
        // GWDM 3.0 SQL section writes) would leave a committed version row with
        // no — or partial — section data. Gwdm30Handler::persist() opens its own
        // transaction; nested here it becomes a savepoint of this outer one.
        $dv = DB::transaction(function () use (
            $isSnapshot,
            $handler,
            $dataset,
            $newGwdmMetadata,
            $previousMetadata,
            $newVersionNumber,
            $versionNumber,
            $title,
            $shortTitle,
            $targetGwdmVersion,
        ) {
            if ($isSnapshot) {
                // For every SNAPSHOT_INTERVAL version we write a full snapshot so that
                // reconstruction never has to walk more than (SNAPSHOT_INTERVAL - 1)
                // deltas. The shape is identical to the base (v1) row.
                $envelope = $handler->buildEnvelope($newGwdmMetadata, $previousMetadata);

                $dv = DatasetVersion::create([
                    'dataset_id' => $dataset->id,
                    'metadata' => json_encode($envelope),
                    // Snapshot version, thus full rebuild of metadata and no patch.
                    'patch' => null,
                    'version' => $newVersionNumber,
                    'title' => $title,
                    'short_title' => $shortTitle,
                    'gwdm_version' => $targetGwdmVersion,
                ]);
            } else {
                // Delta row: reconstruct the current full GWDM object, diff against
                // the new metadata to produce a minimal RFC 6902 patch.
                $currentGwdm = $this->reconstructGwdmMetadata($dataset->id, $versionNumber);
                $patch = $this->computePatch($currentGwdm, $newGwdmMetadata);

                $dv = DatasetVersion::create([
                    'dataset_id' => $dataset->id,
                    // Patch delta, therefore no metadata stored.
                    'metadata' => [],
                    'patch' => $patch,   // array — the cast handles JSON encoding
                    'version' => $newVersionNumber,
                    'title' => $title,
                    'short_title' => $shortTitle,
                    'gwdm_version' => $targetGwdmVersion,
                ]);
            }

            // Delegate to the handler's afterStore hook. For JSON versions this is a
            // no-op; Gwdm30Handler uses it to write structured fields to SQL tables.
            $handler->afterStore($dataset, $dv, $newGwdmMetadata);

            return $dv;
        });

        // Sync the spatial coverage pivot for this new version. mapCoverage()
        // upserts the version's coverage rows and prunes stale ones, and
        // Dataset::getAllSpatialCoveragesAttribute() reads only the latest
        // version's rows — so coverage reflects the current metadata, not a
        // union across all historical versions.
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
        } elseif (is_array($metadata) && ! Arr::has($metadata, 'metadata')) {
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
     * Map metadata coverage/spatial string to the SpatialCoverage controlled list.
     * Mirrors MetadataOnboard@mapCoverage (kept intact for V1).
     */
    private function mapCoverage(array $metadata, DatasetVersion $version): void
    {
        if (! isset($metadata['metadata']['coverage']['spatial'])) {
            return;
        }

        $coverage = strtolower($metadata['metadata']['coverage']['spatial']);
        $allCoverages = SpatialCoverage::all();
        $ukCoverages = $allCoverages->filter(fn ($c) => $c->region !== 'Rest of the world');
        $worldId = $allCoverages->firstWhere('region', 'Rest of the world')?->id;

        // Collect the coverage IDs this version should map to, then sync the pivot
        // (upsert desired + prune stale) so editing coverage DOWN actually removes
        // entries rather than accumulating them.
        $desiredIds = [];

        foreach ($ukCoverages as $c) {
            if (str_contains($coverage, strtolower($c->region))) {
                $desiredIds[] = (int) $c->id;
            }
        }

        if (empty($desiredIds)) {
            if (str_contains($coverage, 'united kingdom')) {
                foreach ($ukCoverages as $c) {
                    $desiredIds[] = (int) $c->id;
                }
            } elseif ($worldId !== null) {
                $desiredIds[] = (int) $worldId;
            }
        }

        foreach ($desiredIds as $coverageId) {
            DatasetVersionHasSpatialCoverage::updateOrCreate([
                'dataset_version_id' => (int) $version->id,
                'spatial_coverage_id' => $coverageId,
            ]);
        }

        // Prune any pivot rows for this version no longer present in the metadata.
        DatasetVersionHasSpatialCoverage::where('dataset_version_id', (int) $version->id)
            ->when(
                ! empty($desiredIds),
                fn ($q) => $q->whereNotIn('spatial_coverage_id', $desiredIds),
            )
            ->delete();
    }
}
