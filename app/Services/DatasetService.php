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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MetadataManagementController as MMC;

class DatasetService
{
    // -------------------------------------------------------------------------
    // Querying
    // -------------------------------------------------------------------------

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
        return Dataset::where('status', Dataset::STATUS_ACTIVE)->find($id);
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
            $translated = MMC::translateDataModelType(
                json_encode($latestVersion->metadata),
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

    // -------------------------------------------------------------------------
    // Mutations
    // -------------------------------------------------------------------------

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

        $version = MMC::createDatasetVersion([
            'dataset_id' => $dataset->id,
            'metadata'   => json_encode($input['metadata']),
            'version'    => 1,
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

        $this->dispatchJobs($dataset, $datasetVersionId, $submittedMetadata, $elasticIndexing, $input['status']);

        return $versionNumber;
    }

    /**
     * Patch a dataset's status only. Dispatches LinkageExtraction if activating.
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

    // -------------------------------------------------------------------------
    // Linkages
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

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
     * Persist an updated metadata snapshot against the current version record.
     * Mirrors MetadataVersioning@updateMetadataVersion (kept intact for V1).
     */
    private function persistMetadataVersion(
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

        $dv = DatasetVersion::where([
            'dataset_id' => $dataset->id,
            'version'    => $versionNumber,
        ])->first();

        $dv->metadata = json_encode($metadataSaveObject);
        $dv->save();

        return $dv->id;
    }

    /**
     * Normalise incoming metadata into a consistent nested shape before
     * passing to TRASER. Handles string JSON, double-nesting, and FMA-style inputs.
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

    private function buildRequiredBlock(Dataset $dataset, int $versionNumber): array
    {
        return [
            'gatewayId'  => strval($dataset->id),
            'gatewayPid' => $dataset->pid,
            'issued'     => $dataset->created,
            'modified'   => $dataset->updated,
            'revisions'  => [[
                'url'     => config('gateway.gateway_url') . '/dataset/' . $dataset->id . '?version=1.0.0',
                'version' => $this->formatVersion($versionNumber),
            ]],
        ];
    }

    private function formatVersion(int $version): string
    {
        return "{$version}.0.0";
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
