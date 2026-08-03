<?php

namespace App\Http\Traits;

use App\Models\Collection;
use App\Models\CollectionHasDatasetVersion;
use App\Models\CollectionHasDur;
use App\Models\DataProviderColl;
use App\Models\DataProviderCollHasTeam;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasTool;
use App\Models\Dur;
use App\Models\DurHasDatasetVersion;
use App\Models\License;
use App\Models\ProgrammingLanguage;
use App\Models\ProgrammingPackage;
use App\Models\Publication;
use App\Models\PublicationHasDatasetVersion;
use App\Models\Sector;
use App\Models\Tag;
use App\Models\Team;
use App\Models\Tool;
use App\Models\ToolHasProgrammingLanguage;
use App\Models\ToolHasProgrammingPackage;
use App\Models\ToolHasTag;
use App\Models\ToolHasTypeCategory;
use App\Models\TypeCategory;
use App\Services\DatasetService;
use App\Services\Gwdm\GwdmHandlerFactory;
use Auditor;
use Config;
use ElasticClientController as ECC;
use Exception;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

trait IndexElastic
{
    use GetValueByPossibleKeys;

    private $datasets = [];

    private $durs = [];

    private $tools = [];

    private $publications = [];

    private $collections = [];

    /**
     * Single named resolution point for DatasetService within this trait.
     *
     * Constructor injection isn't viable here: IndexElastic is a trait consumed
     * by ~20 unrelated controllers/jobs/observers, so there is no single
     * constructor to inject into without a much larger refactor.
     */
    private function datasetService(): DatasetService
    {
        return app(DatasetService::class);
    }

    /**
     * Reconstruct the full GWDM envelope for a dataset's latest version.
     *
     * DatasetVersion->metadata cannot be read directly for indexing: delta rows
     * store an empty metadata blob, and GWDM 3.0 rows omit the `metadata` key
     * entirely (their data lives in dedicated SQL tables). Funnelling through
     * DatasetService::getReconstructedMetadataEnvelope() yields a complete
     * {gwdmVersion, metadata, original_metadata} envelope for every schema
     * version and every snapshot/delta storage shape. Validation is skipped —
     * data is validated at write time.
     *
     * @return array The reconstructed envelope, or [] when the dataset has no versions.
     */
    private function reconstructedLatestEnvelope(Dataset $dataset, ?DatasetVersion $version = null): array
    {
        // Callers pass in an already-resolved version to avoid a second
        // latestVersion() call. Must be a full row (patch + metadata loaded).
        $version ??= $dataset->latestVersion();

        if (! $version) {
            return [];
        }

        return $this->datasetService()->getReconstructedMetadataEnvelope(
            $dataset->id,
            $version->version,
            false,
            $version,
        );
    }

    /**
     * Preload datasets by id (with their versions) keyed by id, so callers can
     * resolve each dataset's latest version in memory rather than issuing a
     * Dataset::where(...)->first() + latestVersion() query per row (N+1).
     *
     * @param  array<int|string>  $ids
     * @return EloquentCollection<int, Dataset>
     */
    private function preloadDatasetsById(array $ids): EloquentCollection
    {
        return Dataset::whereIn('id', array_values(array_unique($ids)))
            ->with(['versions' => fn ($q) => $q->select('id', 'dataset_id', 'version', 'short_title')])
            ->get()
            ->keyBy('id');
    }

    /**
     * Resolve a preloaded dataset's latest version from its already-loaded
     * `versions` relation (no query), mirroring Dataset::latestVersion()'s
     * "highest version number" semantics.
     */
    private function latestLoadedVersion(?Dataset $dataset): ?DatasetVersion
    {
        return $dataset?->versions->sortByDesc('version')->first();
    }

    /**
     * Metadata-derived Elastic fields for a reconstructed envelope, resolved
     * through the version-appropriate GWDM handler. Keeps all GWDM path
     * knowledge in the handlers rather than scattered across this trait.
     */
    private function elasticFieldsFor(array $envelope): array
    {
        return app(GwdmHandlerFactory::class)
            ->resolve($envelope['gwdmVersion'] ?? Config::get('metadata.GWDM.version'))
            ->toElasticFields($envelope);
    }

    private function isElasticIndexable(array $envelope): bool
    {
        $version = $envelope['gwdmVersion'] ?? null;

        if ($version === null) {
            return true; // shouldnt hit here, but perserve original behaviour if
        }

        return app(GwdmHandlerFactory::class)->resolve($version)->supportsElasticIndexing();
    }

    /**
     * Calls a re-indexing of Elastic search when a dataset is created, updated or added to a collection.
     *
     * @param  string  $datasetId  The dataset id from the DB.
     * @param  bool  $returnParams  Optional flag to return parameters.
     */
    public function reindexElastic(string $datasetId, bool $returnParams = false, bool $activeCheck = true): ?array
    {
        try {
            $datasetMatch = Dataset::where('id', $datasetId)
                ->firstOrFail();

            if ($activeCheck) {
                if ($datasetMatch->status !== Dataset::STATUS_ACTIVE) {
                    return null;
                }
            }

            if (DatasetVersion::where('dataset_id', $datasetId)->count() === 0) {
                return null;
                // This has been removed pending further investigation of the behaviour of the observers under GMI deletion.
                // There's a non-zero chance we'll end up with a dataset hanging around for a moment in the index
                // with no metadata associated, but that shouldn't cause upsets.
                // throw new \Exception("Error: DatasetVersion is missing for dataset ID=$datasetId.");
            }

            $latestVersion = $datasetMatch->latestVersion();
            $metadata = $this->reconstructedLatestEnvelope($datasetMatch, $latestVersion);

            if (! $this->isElasticIndexable($metadata)) {
                $this->deleteDatasetFromElastic($datasetId);

                return null;
            }

            // Metadata-derived fields come from the version-appropriate GWDM handler.
            $fields = $this->elasticFieldsFor($metadata);
            // short_title/title are populated columns on every write (see DatasetVersion);
            // only fall back to the envelope for legacy pre-migration rows where they're null.
            $fields['shortTitle'] = $fields['shortTitle'] ?? $latestVersion->short_title;
            $fields['title'] = $fields['title'] ?? $latestVersion->title;

            $projectGrants = $this->projectGrantsForDataset($datasetMatch->id);

            // Relationship/DB-derived fields (not visible to the handler) merged on top.
            $toIndex = array_merge($fields, [
                'named_entities' => array_map(fn ($entity) => $entity['name'], $datasetMatch->allNamedEntities),
                'collectionName' => array_map(fn ($collection) => $collection['name'], $datasetMatch->allCollections),
                'dataUseTitles' => array_map(fn ($dur) => $dur['project_title'], $datasetMatch->allDurs),
                'geographicLocation' => array_map(fn ($spatialCoverage) => $spatialCoverage['region'], $datasetMatch->allSpatialCoverages),
                'dataProviderColl' => DataProviderColl::whereIn('id', DataProviderCollHasTeam::where('team_id', $datasetMatch->team_id)->pluck('data_provider_coll_id'))->pluck('name')->all(),
                'isCohortDiscovery' => $datasetMatch->is_cohort_discovery,
                'partnerContext' => $datasetMatch->partner_context,
                // Project grants (for search & filtering)
                'projectGrants' => $projectGrants,
                'projectGrantNames' => array_values(array_unique(array_filter(array_map(
                    fn (array $g) => $g['projectGrantName'] ?? null,
                    $projectGrants
                )))),
            ]);

            $params = [
                'index' => ECC::ELASTIC_NAME_DATASET,
                'id' => $datasetMatch->id,
                'body' => $toIndex,
                'headers' => 'application/json',
            ];

            if ($returnParams) {
                unset($metadata);

                return $params;
            }
            ECC::indexDocument($params);

            return null;

        } catch (Exception $e) {
            \Log::error('Error reindexing ElasticSearch', [
                'datasetId' => $datasetId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Fetch all project grants linked to a dataset, along with their latest version fields.
     *
     * Returned shape is intentionally small and search-friendly.
     *
     * @return array<int, array<string, mixed>>
     */
    private function projectGrantsForDataset(int $datasetId): array
    {
        $rows = \DB::select(
            '
                SELECT
                    pghd.project_grant_id,
                    pgv.version,
                    pgv.project_grant_name,
                    pgv.lead_researcher,
                    pgv.lead_research_institute,
                    pgv.grant_numbers,
                    pgv.project_grant_start_date,
                    pgv.project_grant_end_date,
                    pgv.project_grant_scope
                FROM project_grant_has_dataset pghd
                LEFT JOIN (
                    SELECT
                        pgv_inner.project_grant_id,
                        pgv_inner.version,
                        pgv_inner.project_grant_name,
                        pgv_inner.lead_researcher,
                        pgv_inner.lead_research_institute,
                        pgv_inner.grant_numbers,
                        pgv_inner.project_grant_start_date,
                        pgv_inner.project_grant_end_date,
                        pgv_inner.project_grant_scope
                    FROM project_grant_versions pgv_inner
                    INNER JOIN (
                        SELECT project_grant_id, MAX(version) AS max_version
                        FROM project_grant_versions
                        WHERE deleted_at IS NULL
                        GROUP BY project_grant_id
                    ) latest
                        ON latest.project_grant_id = pgv_inner.project_grant_id
                        AND latest.max_version = pgv_inner.version
                    WHERE pgv_inner.deleted_at IS NULL
                ) pgv ON pgv.project_grant_id = pghd.project_grant_id
                WHERE pghd.dataset_id = :dataset_id
            ',
            [
                'dataset_id' => $datasetId,
            ]
        );

        if (! count($rows)) {
            return [];
        }

        return array_values(array_map(function ($row) {
            if ($row->version === null) {
                return ['project_grant_id' => (int) $row->project_grant_id];
            }

            $grantNumbers = $row->grant_numbers;
            if (is_string($grantNumbers)) {
                $grantNumbers = json_decode($grantNumbers, true);
            }

            return [
                'project_grant_id' => (int) $row->project_grant_id,
                'version' => (int) $row->version,
                'projectGrantName' => $row->project_grant_name,
                'leadResearcher' => $row->lead_researcher,
                'leadResearchInstitute' => $row->lead_research_institute,
                'grantNumbers' => $grantNumbers,
                'projectGrantStartDate' => $row->project_grant_start_date,
                'projectGrantEndDate' => $row->project_grant_end_date,
                'projectGrantScope' => $row->project_grant_scope,
            ];
        }, $rows));
    }

    /**
     * Calls a re-indexing of Elastic search when a data provider id is given.
     *
     * @param  string  $teamId  The team id from the DB.
     * @param  bool  $returnParams  Optional flag to return parameters.
     */
    public function reindexElasticDataProvider(string $teamId, bool $returnParams = false): ?array
    {
        try {
            $datasets = Dataset::where([
                'team_id' => $teamId,
                'status' => Dataset::STATUS_ACTIVE,
            ]);

            if (is_null($datasets->first())) {
                return null;
            }

            $datasets = $datasets->get();

            $datasetTitles = [];
            $datasetVersionIds = [];
            $locations = [];
            $dataTypes = [];
            $publicationTitles = [];
            $collectionNames = [];
            $durTitles = [];
            $toolNames = [];
            foreach ($datasets as $dataset) {
                $dataset->setAttribute('spatialCoverage', $dataset->allSpatialCoverages);
                foreach ($dataset['spatialCoverage'] as $loc) {
                    if (! in_array($loc['region'], $locations)) {
                        $locations[] = $loc['region'];
                    }
                }

                $latestVersion = $dataset->latestVersion();
                if ($latestVersion) {
                    $metadata = $this->reconstructedLatestEnvelope($dataset, $latestVersion);

                    if ($this->isElasticIndexable($metadata)) {
                        $fields = $this->elasticFieldsFor($metadata);
                        $datasetVersionIds[] = $latestVersion->id;
                        $datasetTitles[] = $latestVersion->short_title ?? $fields['shortTitle'];
                        foreach ($fields['dataType'] as $t) {
                            if (! in_array($t, $dataTypes)) {
                                $dataTypes[] = $t;
                            }
                        }
                    }
                }

                unset($metadata); // Only because it's potentially massive.
            }
            usort($datasetTitles, 'strcasecmp');

            // dur
            if (count($datasetVersionIds)) {
                $durHasDatasetVersions = DurHasDatasetVersion::whereIn('dataset_version_id', $datasetVersionIds)->select('dur_id')->get();
                $durIds = convertArrayToArrayWithKeyName($durHasDatasetVersions, 'dur_id');
                $durs = Dur::whereIn('id', $durIds)->select('project_title')->get();
                $durTitles = convertArrayToArrayWithKeyName($durs, 'project_title');
                $durByTeamIds = Dur::where('team_id', $teamId)->select('project_title')->get();
                $durByTeamIdTitles = convertArrayToArrayWithKeyName($durByTeamIds, 'project_title');
                $durTitles = implode(',', array_unique(array_merge($durTitles, $durByTeamIdTitles)));
            }

            // tools
            if (count($datasetVersionIds)) {
                $datasetVersionHasTools = DatasetVersionHasTool::whereIn('dataset_version_id', $datasetVersionIds)->select('tool_id')->get();
                $toolIds = convertArrayToArrayWithKeyName($datasetVersionHasTools, 'tool_id');
                $tools = Tool::whereIn('id', $toolIds)->select('name')->get();
                $toolNames = convertArrayToStringWithKeyName($tools, 'name');
            }

            // publications
            if (count($datasetVersionIds)) {
                $publicationHasDatasetVersions = PublicationHasDatasetVersion::whereIn('dataset_version_id', $datasetVersionIds)->select('publication_id')->get();
                $publicationIds = convertArrayToArrayWithKeyName($publicationHasDatasetVersions, 'publication_id');
                $publications = Publication::whereIn('id', $publicationIds)->select('paper_title')->get();
                $publicationTitles = convertArrayToStringWithKeyName($publications, 'paper_title');
            }

            // collections
            if (count($datasetVersionIds)) {
                $collectionHasDatasetVersions = CollectionHasDatasetVersion::whereIn('dataset_version_id', $datasetVersionIds)->select('collection_id')->get();
                $collectionIds = convertArrayToArrayWithKeyName($collectionHasDatasetVersions, 'collection_id');
                $collections = Collection::whereIn('id', $collectionIds)->where('status', 'active')->select('name')->get();
                $collectionNames = convertArrayToStringWithKeyName($collections, 'name');
            }

            // aliases
            $aliases = Team::where('id', $teamId)->with(['aliases'])->select(['id'])->first();
            $aliases = $aliases->toArray();
            $aliases = $aliases['aliases'] ?? [];
            $aliases = array_map(function ($alias) {
                return $alias['name'];
            }, $aliases);

            $toIndex = [
                'name' => Team::findOrFail($teamId)->name,
                'datasetTitles' => array_values(array_unique($datasetTitles)),
                'geographicLocation' => $locations,
                'dataType' => $dataTypes,
                'durTitles' => $durTitles,
                'toolNames' => $toolNames,
                'publicationTitles' => $publicationTitles,
                'collectionNames' => $collectionNames,
                'teamAliases' => $aliases,
            ];

            $params = [
                'index' => 'dataprovider',
                'id' => $teamId,
                'body' => $toIndex,
                'headers' => 'application/json',
            ];
            if ($returnParams) {
                return $params;
            }

            ECC::indexDocument($params);

            return null;

        } catch (Exception $e) {
            \Log::error('Error reindexing ElasticSearch', [
                'teamId' => $teamId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception($e->getMessage());
        }
    }

    public function reindexElasticDataProviderWithRelations(string $teamId, string $relation = 'undefined')
    {
        try {
            $team = Team::where('id', $teamId)->select(['id', 'pid', 'name'])->first();
            if (is_null($team)) {
                return;
            }
            $team = $team->toArray();

            $this->reindexElasticDataProvider($teamId);

            if ($relation === 'dataset' || $relation === 'undefined') {
                $datasets = Dataset::where('team_id', $teamId)->select(['id', 'status'])->get();
                foreach ($datasets as $dataset) {
                    if ($dataset->status === Dataset::STATUS_ACTIVE) {
                        $this->reindexElastic($dataset->id);
                    }
                }
            }

            if ($relation === 'collection' || $relation === 'undefined') {
                $collections = Collection::where('team_id', $teamId)->select(['id', 'status'])->get();
                foreach ($collections as $collection) {
                    if ($collection->status === Collection::STATUS_ACTIVE) {
                        $this->indexElasticCollections($collection->id);
                    }
                }
            }

            if ($relation === 'dur' || $relation === 'undefined') {
                $durs = Dur::where('team_id', $teamId)->select(['id', 'status'])->get();
                foreach ($durs as $dur) {
                    if ($dur->status === Dur::STATUS_ACTIVE) {
                        $this->indexElasticDur($dur->id);
                    }
                }
            }

            if ($relation === 'tool' || $relation === 'undefined') {
                $tools = Tool::where('team_id', $teamId)->select(['id', 'status'])->get();
                foreach ($tools as $tool) {
                    if ($tool->status === Tool::STATUS_ACTIVE) {
                        $this->indexElasticTools($tool->id);
                    }
                }
            }
        } catch (Exception $e) {
            \Log::error('Error reindexing ElasticSearch', [
                'teamId' => $teamId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception($e->getMessage());
        }

    }

    /**
     * Insert collection document into elastic index
     *
     * @param  bool  $returnParams  Optional flag to return parameters.
     */
    public function indexElasticCollections(int $collectionId, bool $returnParams = false): ?array
    {
        $collection = Collection::with(['team', 'keywords'])->where('id', $collectionId)->first();
        $datasets = $collection->allDatasets ?? [];

        $datasetIds = array_map(function ($dataset) {
            return $dataset['id'];
        }, $datasets);

        $collection = $collection->toArray();
        $team = $collection['team'];

        $datasetTitles = [];
        $datasetAbstracts = [];
        $datasetsById = $this->preloadDatasetsById($datasetIds);
        foreach ($datasetIds as $d) {
            $datasetRow = $datasetsById->get($d);
            $latestVersion = $this->latestLoadedVersion($datasetRow);
            if (! $latestVersion) {
                continue;
            }

            $metadata = $this->reconstructedLatestEnvelope($datasetRow);
            if (! $this->isElasticIndexable($metadata)) {
                continue;
            }

            $fields = $this->elasticFieldsFor($metadata);
            $datasetTitles[] = $latestVersion->short_title ?? $fields['shortTitle'];
            $datasetAbstracts[] = $fields['abstract'];
        }

        $keywords = [];
        foreach ($collection['keywords'] as $k) {
            $keywords[] = $k['name'];
        }

        $dataProviderColl = [];
        if (array_key_exists('team_id', $collection)) {
            $dataProviderCollId = DataProviderCollHasTeam::where('team_id', $collection['team_id'])
                ->pluck('data_provider_coll_id')
                ->all();
            $dataProviderColl = DataProviderColl::whereIn('id', $dataProviderCollId)
                ->pluck('name')
                ->all();
        }

        try {
            $toIndex = [
                'publisherName' => isset($team['name']) ? $team['name'] : '',
                'description' => $collection['description'],
                'name' => $collection['name'],
                'datasetTitles' => $datasetTitles,
                'datasetAbstracts' => $datasetAbstracts,
                'keywords' => $keywords,
                'dataProviderColl' => $dataProviderColl,
            ];
            $params = [
                'index' => ECC::ELASTIC_NAME_COLLECTION,
                'id' => $collectionId,
                'body' => $toIndex,
                'headers' => 'application/json',
            ];

            if ($returnParams) {
                return $params;
            }
            ECC::indexDocument($params);

            return null;

        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert data provider document into elastic index
     */
    private function indexElasticDataProviderColl(int $id): void
    {
        $provider = DataProviderColl::where('id', $id)->with('teams')->first();

        $datasetTitles = [];
        $locations = [];
        foreach ($provider['teams'] as $team) {
            $datasets = Dataset::where('team_id', $team['id'])
                ->with(['versions' => fn ($q) => $q->select('id', 'dataset_id', 'version', 'short_title')])
                ->get();

            foreach ($datasets as $dataset) {
                $dataset->setAttribute('spatialCoverage', $dataset->allSpatialCoverages ?? []);
                $datasetTitles[] = $this->latestLoadedVersion($dataset)?->short_title ?? '';
                foreach ($dataset['spatialCoverage'] as $loc) {
                    if (! in_array($loc['region'], $locations)) {
                        $locations[] = $loc['region'];
                    }
                }
            }
        }
        usort($datasetTitles, 'strcasecmp');

        try {
            $toIndex = [
                'name' => $provider['name'],
                'datasetTitles' => $datasetTitles,
                'geographicLocation' => $locations,
            ];
            $params = [
                'index' => ECC::ELASTIC_NAME_DATAPROVIDERCOLL,
                'id' => $id,
                'body' => $toIndex,
                'headers' => 'application/json',
            ];

            ECC::indexDocument($params);

        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Calls a re-indexing of Elastic search when a data use is created or updated
     *
     * @param  string  $id  The dur id from the DB
     * @param  bool  $returnParams  Optional flag to return parameters.
     */
    public function indexElasticDur(string $id, bool $returnParams = false): ?array
    {
        try {

            $durMatch = Dur::where(['id' => $id])
                ->with(['keywords', 'team', 'sector'])
                ->first();

            $datasets = $durMatch->allDatasets ?? [];

            $datasetIds = array_map(function ($dataset) {
                return $dataset['id'];
            }, $datasets);

            $durMatch = $durMatch->toArray();

            $datasetTitles = [];
            $datasetsById = $this->preloadDatasetsById($datasetIds);
            foreach ($datasetIds as $d) {
                $datasetTitles[] = $this->latestLoadedVersion($datasetsById->get($d))?->short_title ?? '';
            }

            $keywords = [];
            foreach ($durMatch['keywords'] as $k) {
                $keywords[] = $k['name'];
            }

            $sector = ($durMatch['sector'] != null) ? Sector::where(
                [
                    'id' => $durMatch['sector'],
                ]
            )->first()->name : null;

            $dataProviderId = DataProviderCollHasTeam::where('team_id', $durMatch['team_id'])
                ->pluck('data_provider_coll_id')
                ->all();
            $dataProvider = DataProviderColl::whereIn('id', $dataProviderId)
                ->pluck('name')
                ->all();

            $toIndex = [
                'projectTitle' => $durMatch['project_title'],
                'laySummary' => $durMatch['lay_summary'],
                'publicBenefitStatement' => $durMatch['public_benefit_statement'],
                'technicalSummary' => $durMatch['technical_summary'],
                'fundersAndSponsors' => $durMatch['funders_and_sponsors'],
                'publisherName' => $durMatch['team']['name'],
                'organisationName' => $durMatch['organisation_name'],
                'datasetTitles' => $datasetTitles,
                'keywords' => $keywords,
                'sector' => $sector,
                'dataProvider' => $dataProvider,
                'collectionNames' => $this->getCollectionNamesByDurId($id),
            ];

            $params = [
                'index' => ECC::ELASTIC_NAME_DUR,
                'id' => $id,
                'body' => $toIndex,
                'headers' => 'application/json',
            ];

            if ($returnParams) {
                return $params;
            }

            ECC::indexDocument($params);

            return null;

        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Calls a re-indexing of Elastic search when a publication is created or updated
     *
     * @param  string  $id  The publication id from the DB
     * @param  bool  $returnParams  Optional flag to return parameters.
     */
    public function indexElasticPublication(string $id, bool $returnParams = false): ?array
    {
        try {
            $pubMatch = Publication::where(['id' => $id])->with(['keywords:id,name'])->first();
            $datasets = $pubMatch->allDatasets;

            $keywords = array_unique($pubMatch?->keywords->pluck('name')->toArray() ?? []);

            $datasetTitles = [];
            $datasetLinkTypes = [];

            // Calum 01/10/2024
            // - Database linktype is USING/ABOUT/UNKNOWN
            // - we have been requested to change this display text on the FE
            // - however the FE has to render what the filter returns
            // - I think it would be better to translate these mappings on the FE
            // - however, due to time constraints, it could mess up lots of other filters
            // - for now, because the link type is a static ENUM, we can map this on the BE
            // - will raise for post-MVP discussion

            $linkTypeMappings = [
                'USING' => 'Using a dataset',
                'ABOUT' => 'About a dataset',
                'UNKNOWN' => 'Unknown',
            ];

            $datasetIds = collect($datasets)->pluck('id')->all();
            $datasetsById = $this->preloadDatasetsById($datasetIds);

            // Resolve each dataset's latest version once, then batch the
            // link-type lookup, rather than querying per row (N+1).
            $latestVersionByDataset = [];
            foreach ($datasetIds as $datasetId) {
                $latestVersionByDataset[$datasetId] = $this->latestLoadedVersion($datasetsById->get($datasetId));
            }

            $linkTypeByVersionId = PublicationHasDatasetVersion::where('publication_id', (int) $id)
                ->whereIn('dataset_version_id', collect($latestVersionByDataset)->filter()->map->id->all())
                ->pluck('link_type', 'dataset_version_id');

            foreach ($datasets as $dataset) {
                $latestVersion = $latestVersionByDataset[$dataset['id']] ?? null;
                $datasetTitles[] = $latestVersion?->short_title ?? '';

                // change from 'UNKNOWN' to 'USING' - temp
                $linkType = ($latestVersion ? ($linkTypeByVersionId[$latestVersion->id] ?? null) : null) ?? 'USING';

                $datasetLinkTypes[] = array_key_exists($linkType, $linkTypeMappings) ? $linkTypeMappings[$linkType] : 'Unknown';
            }

            // Split string to array of strings
            $publicationTypes = explode(',', $pubMatch['publication_type']);

            // replace any empty strings with Research articles
            foreach ($publicationTypes as $i => $value) {
                if ($value === '') {
                    $publicationTypes[$i] = 'Research articles';
                }
            }

            $toIndex = [
                'title' => $pubMatch['paper_title'],
                'journalName' => $pubMatch['journal_name'],
                'abstract' => $pubMatch['abstract'],
                'authors' => $pubMatch['authors'],
                'publicationDate' => $pubMatch['year_of_publication'],
                'doi' => $pubMatch['paper_doi'],
                'datasetTitles' => $datasetTitles,
                'publicationType' => $publicationTypes,
                'datasetLinkTypes' => $datasetLinkTypes,
                'keywords' => $keywords,
            ];
            $params = [
                'index' => ECC::ELASTIC_NAME_PUBLICATION,
                'id' => $id,
                'body' => $toIndex,
                'headers' => 'application/json',
            ];
            if ($returnParams) {
                return $params;
            }

            ECC::indexDocument($params);

            return null;

        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert tool document into elastic index
     *
     * @param  bool  $returnParams  Optional flag to return parameters.
     */
    public function indexElasticTools(int $toolId, bool $returnParams = false): ?array
    {
        try {
            $tool = Tool::where('id', $toolId)
                ->with([
                    'programmingLanguages',
                    'programmingPackages',
                    'tag',
                    'category',
                    'typeCategory',
                    'license',
                    'team',
                ])
                ->first();

            $license = License::where('id', $tool['license'])->first();

            $typeCategoriesIDs = ToolHasTypeCategory::where('tool_id', $toolId)
                ->pluck('type_category_id')
                ->all();

            $typeCategories = TypeCategory::where('id', $typeCategoriesIDs)
                ->pluck('name')
                ->all();

            $programmingLanguagesIDs = ToolHasProgrammingLanguage::where('tool_id', $toolId)
                ->pluck('programming_language_id')
                ->all();

            $programmingLanguages = ProgrammingLanguage::where('id', $programmingLanguagesIDs)
                ->pluck('name')
                ->all();

            $programmingPackagesIDs = ToolHasProgrammingPackage::where('tool_id', $toolId)
                ->pluck('programming_package_id')
                ->all();

            $programmingPackages = ProgrammingPackage::where('id', $programmingPackagesIDs)
                ->pluck('name')
                ->all();

            $tagIDs = ToolHasTag::where('tool_id', $toolId)
                ->pluck('tag_id')
                ->all();

            $tags = Tag::where('id', $tagIDs)
                ->pluck('description')
                ->all();

            $datasetVersionIDs = DatasetVersionHasTool::where('tool_id', $toolId)
                ->pluck('dataset_version_id')
                ->all();

            $datasetIDs = DatasetVersion::whereIn('id', $datasetVersionIDs)
                ->pluck('dataset_id')
                ->all();

            $datasets = Dataset::whereIn('id', $datasetIDs)
                ->with(['versions' => fn ($q) => $q->select('id', 'dataset_id', 'version', 'short_title')])
                ->get();

            $dataProviderCollId = DataProviderCollHasTeam::where('team_id', $tool['team_id'])
                ->pluck('data_provider_coll_id')
                ->all();

            $dataProviderColl = DataProviderColl::whereIn('id', $dataProviderCollId)
                ->pluck('name')
                ->all();

            $datasetTitles = [];
            if ($tool->any_dataset) {
                $datasetTitles[] = '_Can be used with any dataset';
            } else {
                foreach ($datasets as $dataset) {
                    $datasetTitles[] = $this->latestLoadedVersion($dataset)?->short_title ?? '';
                }
                usort($datasetTitles, 'strcasecmp');
            }

            $toIndex = [
                'name' => $tool['name'],
                'description' => $tool['description'],
                'license' => $license ? $license['label'] : null,
                'techStack' => $tool['tech_stack'],
                'category' => $tool['category'] ? $tool['category']['name'] : '',
                'typeCategory' => $typeCategories,
                'associatedAuthors' => $tool['associated_authors'],
                'programmingLanguages' => $programmingLanguages,
                'programmingPackages' => $programmingPackages,
                'tags' => $tags,
                'datasetTitles' => $datasetTitles,
                'dataProviderColl' => $dataProviderColl,
                'dataProvider' => $tool['team']['name'] ?? null,
                'resultsInsights' => $tool['results_insights'],
            ];

            $params = [
                'index' => ECC::ELASTIC_NAME_TOOL,
                'id' => $toolId,
                'body' => $toIndex,
                'headers' => 'application/json',
            ];

            if ($returnParams) {
                return $params;
            }

            ECC::indexDocument($params);

            return null;

        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Reindex in bulk
     *
     * @param  callable  $indexer  Name of single index to call to get parameters
     */
    public function reindexElasticBulk(array $ids, callable $indexer): void
    {
        $bulkParams = [];
        foreach ($ids as $id) {
            $check = $indexer($id, true);
            if (is_null($check)) {
                continue;
            }
            $bulkParams[] = $indexer($id, true);
        }
        ECC::indexBulk($bulkParams);
        unset($bulkParams);
    }

    /**
     * Calls a delete on the document in ElasticSearch index when a dataset is
     * deleted
     *
     * @param  string  $id  The id of the dataset to be deleted
     * @param  string  $indexType  index type: dataset, publication
     */
    public function deleteFromElastic(string $id, string $indexType): void
    {
        try {

            $params = [
                'index' => $indexType,
                'id' => $id,
                'headers' => 'application/json',
            ];

            ECC::deleteDocument($params);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function deleteDurFromElastic(string $id)
    {
        $this->deleteFromElastic($id, ECC::ELASTIC_NAME_DUR);
    }

    public function deleteCollectionFromElastic(string $id)
    {
        $this->deleteFromElastic($id, ECC::ELASTIC_NAME_COLLECTION);
    }

    public function deletePublicationFromElastic(string $id)
    {
        $this->deleteFromElastic($id, ECC::ELASTIC_NAME_PUBLICATION);
    }

    public function deleteToolFromElastic(string $id)
    {
        $this->deleteFromElastic($id, ECC::ELASTIC_NAME_TOOL);
    }

    public function deleteDatasetFromElastic(string $id)
    {
        $this->deleteFromElastic($id, ECC::ELASTIC_NAME_DATASET);
    }

    public function deleteDataProvider(string $id)
    {
        $this->deleteFromElastic($id, ECC::ELASTIC_NAME_DATAPROVIDER);
    }

    public function deleteDataProviderCollFromElastic(string $id)
    {
        try {
            $this->deleteFromElastic($id, ECC::ELASTIC_NAME_DATAPROVIDERCOLL);
        } catch (Exception $e) {
            // Elastic is being incrementally replaced by Typesense for this
            // entity, so a broken/unreachable Elastic cluster shouldn't block
            // deletion of the underlying record — log and move on rather
            // than re-throwing (mirrors indexElasticDataCustodianNetwork()).
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Insert tool document into elastic index
     *
     * @param  bool  $returnParams  Optional flag to return parameters.
     */
    public function indexElasticDataCustodianNetwork(int $dataCustodianNetworkId, bool $returnParams = false): ?array
    {
        try {
            $dpc = DataProviderColl::select('id', 'name', 'img_url', 'enabled', 'url', 'service', 'summary')
                ->with('teams')
                ->where('id', $dataCustodianNetworkId)
                ->first();

            // A disabled (or since-deleted) network shouldn't be searchable —
            // skip indexing rather than crashing on a null $dpc below, which
            // happened whenever this ran right after creating/disabling a
            // network with enabled=false (the query used to filter on
            // enabled=1 here too, so it always came back empty in that case).
            if (! $dpc || ! $dpc->enabled) {
                return null;
            }

            $teamsResult = $this->getInfoTeams($dpc);

            $durs = Dur::select(['id', 'project_title'])->whereIn('id', $this->durs)->get()->toArray();
            $tools = Tool::select(['id', 'name'])->with(['user'])->whereIn('id', $this->tools)->get()->toArray();
            $publications = Publication::select(['id', 'paper_title'])->whereIn('id', $this->publications)->get()->toArray();
            $collections = Collection::select(['id', 'name'])->whereIn('id', $this->collections)->get()->toArray();

            $toIndex = [
                'name' => $dpc->name,
                'summary' => $dpc->summary,
                'publisherNames' => array_map(function ($item) {
                    return $item['name'];
                }, $teamsResult),
                'datasetTitles' => array_map(function ($item) {
                    return $item['title'];
                }, $this->datasets),
                'durTitles' => array_map(function ($item) {
                    return $item['project_title'];
                }, $durs),
                'toolNames' => array_map(function ($item) {
                    return $item['name'];
                }, $tools),
                'publicationTitles' => array_map(function ($item) {
                    return $item['paper_title'];
                }, $publications),
                'collectionNames' => array_map(function ($item) {
                    return $item['name'];
                }, $collections),
            ];
            $params = [
                'index' => ECC::ELASTIC_NAME_DATACUSTODIANNETWORK,
                'id' => $dataCustodianNetworkId,
                'body' => $toIndex,
                'headers' => 'application/json',
            ];

            if ($returnParams) {
                return $params;
            }

            ECC::indexDocument($params);

            return null;
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            // Elastic is being incrementally replaced by Typesense for this
            // entity, so a broken/unreachable Elastic cluster (e.g. bad
            // local credentials) shouldn't block create/update/delete of the
            // underlying record — log and move on rather than re-throwing.
            return null;
        }
    }

    public function getInfoTeams(DataProviderColl $dp)
    {
        $idTeams = DataProviderCollHasTeam::where(['data_provider_coll_id' => $dp->id])->pluck('team_id')->toArray();
        $teamsResult = [];

        foreach ($idTeams as $idTeam) {
            $team = Team::select('id', 'name')->where(['id' => $idTeam])->first();
            if (is_null($team)) {
                continue;
            }

            $this->getDatasets((int) $team->id);
            $teamsResult[] = [
                'name' => $team->name,
                'id' => $team->id,
            ];
        }

        return $teamsResult;
    }

    public function getDatasets(int $teamId)
    {
        $datasetIds = Dataset::where(['team_id' => $teamId])->pluck('id')->toArray();

        foreach ($datasetIds as $datasetId) {
            $datasetResources = $this->checkingDataset($datasetId);
        }

        return true;
    }

    public function checkingDataset(int $datasetId)
    {
        $dataset = Dataset::where(['id' => $datasetId])->first();

        // Accessed through the accessor
        $durIds = array_column($dataset->allActiveDurs, 'id') ?? [];
        $collectionIds = array_column($dataset->allActiveCollections, 'id') ?? [];
        $publicationIds = array_column($dataset->allActivePublications, 'id') ?? [];
        $toolIds = array_column($dataset->allActiveTools, 'id') ?? [];

        $latestVersion = $dataset->latestVersion();
        $envelope = $this->reconstructedLatestEnvelope($dataset, $latestVersion);

        // title/short_title are populated columns on every write; only fall back
        // to the reconstructed envelope for legacy pre-migration rows.
        $title = $latestVersion?->title ?? $this->elasticFieldsFor($envelope)['title'];

        $this->datasets[] = [
            'title' => $title,
        ];

        $this->durs = array_unique(array_merge($this->durs, $durIds));
        $this->publications = array_unique(array_merge($this->publications, $publicationIds));
        $this->tools = array_unique(array_merge($this->tools, $toolIds));
        $this->collections = array_unique(array_merge($this->collections, $collectionIds));

        $datasetResources = [
            'durs' => $durIds,
            'publications' => $publicationIds,
            'tools' => $toolIds,
            'collections' => $collectionIds,
        ];

        return $datasetResources;
    }

    public function getCollectionNamesByDurId(int $durId): array
    {
        $collectionNames = [];

        $collectionHasDurs = CollectionHasDur::where([
            'dur_id' => $durId,
        ])
            ->select('collection_id')
            ->get()
            ->toArray();

        if (! count($collectionHasDurs)) {
            return $collectionNames;
        }
        $collectionIds = convertArrayToArrayWithKeyName($collectionHasDurs, 'collection_id');
        $collectionNames = Collection::where('status', Collection::STATUS_ACTIVE)
            ->whereIn('id', $collectionIds)
            ->select('name')
            ->get()
            ->toArray();

        return convertArrayToArrayWithKeyName($collectionNames, 'name');
    }
}
