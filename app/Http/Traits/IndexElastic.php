<?php

namespace App\Http\Traits;

use Auditor;
use Config;
use Exception;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

use App\Models\Collection;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasTool;
use App\Models\DataProviderColl;
use App\Models\DataProviderCollHasTeam;
use App\Models\Dur;
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


use ElasticClientController as ECC;

trait IndexElastic
{
    use GetValueByPossibleKeys;

    private $datasets = [];
    private $durs = [];
    private $tools = [];
    private $publications = [];
    private $collections = [];

    /**
     * Calls a re-indexing of Elastic search when a dataset is created, updated or added to a collection.
     *
     * @param string $datasetId The dataset id from the DB.
     * @param bool $returnParams Optional flag to return parameters.
     *
     * @return null|array
     */
    public function reindexElastic(string $datasetId, bool $returnParams = false, bool $activeCheck = true): null|array
    {
        try {
            $datasetMatch = Dataset::where('id', $datasetId)
                ->firstOrFail();

            if($activeCheck) {
                if($datasetMatch->status !== Dataset::STATUS_ACTIVE) {
                    return null;
                }
            }

            if (DatasetVersion::where('dataset_id', $datasetId)->count() === 0) {
                throw new \Exception("Error: DatasetVersion is missing for dataset ID=$datasetId.");
            }

            $metadata = $datasetMatch->latestVersion()->metadata;

            // inject relationships via Local functions
            $materialTypes = $this->getMaterialTypes($metadata);
            $containsTissue = $this->getContainsTissues($materialTypes);

            $toIndex = [
                'abstract' => $this->getValueByPossibleKeys($metadata, ['metadata.summary.abstract'], ''),
                'keywords' => $this->getValueByPossibleKeys($metadata, ['metadata.summary.keywords'], ''),
                'description' => $this->getValueByPossibleKeys($metadata, ['metadata.summary.description'], ''),
                'shortTitle' => $this->getValueByPossibleKeys($metadata, ['metadata.summary.shortTitle'], ''),
                'title' => $this->getValueByPossibleKeys($metadata, ['metadata.summary.title'], ''),
                'populationSize' => $this->getValueByPossibleKeys($metadata, ['metadata.summary.populationSize'], -1),
                'publisherName' => $this->getValueByPossibleKeys($metadata, ['metadata.summary.publisher.name', 'metadata.summary.publisher.publisherName'], ''),
                'startDate' => $this->getValueByPossibleKeys($metadata, ['metadata.provenance.temporal.startDate'], null),
                'endDate' => $this->getValueByPossibleKeys($metadata, ['metadata.provenance.temporal.endDate'], Carbon::now()->addYears(5)),
                'dataType' => explode(';,;', $this->getValueByPossibleKeys($metadata, ['metadata.summary.datasetType'], '')),
                'dataSubType' => explode(';,;', $this->getValueByPossibleKeys($metadata, ['metadata.summary.datasetSubType'], '')),
                'containsTissue' => $containsTissue,
                'sampleAvailability' => $materialTypes,
                'conformsTo' => explode(';,;', $this->getValueByPossibleKeys($metadata, ['metadata.accessibility.formatAndStandards.conformsTo'], '')),
                'hasTechnicalMetadata' => (bool) count($this->getValueByPossibleKeys($metadata, ['metadata.structuralMetadata'], [])),
                'named_entities' =>  array_map(fn ($entity) => $entity['name'], $datasetMatch->allNamedEntities),
                'collectionName' => array_map(fn ($collection) => $collection['name'], $datasetMatch->allCollections),
                'dataUseTitles' => array_map(fn ($dur) => $dur['project_title'], $datasetMatch->allDurs),
                'geographicLocation' => array_map(fn ($spatialCoverage) => $spatialCoverage['region'], $datasetMatch->allSpatialCoverages),
                'accessService' => $this->getValueByPossibleKeys($metadata, ['metadata.accessibility.access.accessServiceCategory'], null),
                'datasetDOI' => $this->getValueByPossibleKeys($metadata, ['metadata.summary.doiName'], ''),
                'dataProviderColl' => DataProviderColl::whereIn('id', DataProviderCollHasTeam::where('team_id', $datasetMatch->team_id)->pluck('data_provider_coll_id'))->pluck('name')->all(),
            ];


            $params = [
                'index' => ECC::ELASTIC_NAME_DATASET,
                'id' => $datasetMatch->id,
                'body' => $toIndex,
                'headers' => 'application/json'
            ];

            if($returnParams) {
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
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Calls a re-indexing of Elastic search when a data provider id is given.
     *
     * @param string $teamId The team id from the DB.
     * @param bool $returnParams Optional flag to return parameters.
     *
     * @return null|array
     */
    public function reindexElasticDataProvider(string $teamId, bool $returnParams = false): null|array
    {
        try {
            $datasets = Dataset::where('team_id', $teamId)->get();

            $datasetTitles = array();
            $locations = array();
            $dataTypes = array();
            foreach ($datasets as $dataset) {
                $dataset->setAttribute('spatialCoverage', $dataset->allSpatialCoverages);
                $metadata = $dataset->latestVersion()->metadata;
                $datasetTitles[] = $metadata['metadata']['summary']['shortTitle'];
                $types = explode(';,;', $metadata['metadata']['summary']['datasetType']);
                foreach ($types as $t) {
                    if (!in_array($t, $dataTypes)) {
                        $dataTypes[] = $t;
                    }
                }
                foreach ($dataset['spatialCoverage'] as $loc) {
                    if (!in_array($loc['region'], $locations)) {
                        $locations[] = $loc['region'];
                    }
                }

                unset($metadata); // Only because it's potentially massive.
            }
            usort($datasetTitles, 'strcasecmp');

            $toIndex = [
                'name' => Team::findOrFail($teamId)->name,
                'datasetTitles' => $datasetTitles,
                'geographicLocation' => $locations,
                'dataType' => $dataTypes,
            ];

            $params = [
                'index' => 'dataprovider',
                'id' => $teamId,
                'body' => $toIndex,
                'headers' => 'application/json'
            ];
            if($returnParams) {
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

    /**
     * Insert collection document into elastic index
     *
     * @param integer $collectionId
     * @param bool $returnParams Optional flag to return parameters.
     * @return null|array
     */
    public function indexElasticCollections(int $collectionId, bool $returnParams = false): null|array
    {
        $collection = Collection::with(['team', 'keywords'])->where('id', $collectionId)->first();
        $datasets = $collection->allDatasets  ?? [];

        $datasetIds = array_map(function ($dataset) {
            return $dataset['id'];
        }, $datasets);

        $collection = $collection->toArray();
        $team = $collection['team'];

        $datasetTitles = array();
        $datasetAbstracts = array();
        foreach ($datasetIds as $d) {
            $metadata = Dataset::where(['id' => $d])
                ->first()
                ->latestVersion()
                ->metadata;
            $datasetTitles[] = $metadata['metadata']['summary']['shortTitle'];
            $datasetAbstracts[] = $metadata['metadata']['summary']['abstract'];
        }

        $keywords = array();
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
                'dataProviderColl' => $dataProviderColl
            ];
            $params = [
                'index' => ECC::ELASTIC_NAME_COLLECTION,
                'id' => $collectionId,
                'body' => $toIndex,
                'headers' => 'application/json'
            ];

            if($returnParams) {
                return $params;
            }
            ECC::indexDocument($params);
            return null;

        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert data provider document into elastic index
     *
     * @param integer $id
     * @return void
     */
    private function indexElasticDataProviderColl(int $id): void
    {
        $provider = DataProviderColl::where('id', $id)->with('teams')->first();


        $datasetTitles = array();
        $locations = array();
        foreach ($provider['teams'] as $team) {
            $datasets = Dataset::where('team_id', $team['id'])->with(['versions'])->get();

            foreach ($datasets as $dataset) {
                $dataset->setAttribute('spatialCoverage', $dataset->allSpatialCoverages  ?? []);
                $metadata = $dataset['versions'][0];
                $datasetTitles[] = $metadata['metadata']['metadata']['summary']['shortTitle'];
                foreach ($dataset['spatialCoverage'] as $loc) {
                    if (!in_array($loc['region'], $locations)) {
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
                'headers' => 'application/json'
            ];

            ECC::indexDocument($params);

        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Calls a re-indexing of Elastic search when a data use is created or updated
     *
     * @param string $id The dur id from the DB
     * @param bool $returnParams Optional flag to return parameters.
     *
     * @return null|array
     */
    public function indexElasticDur(string $id, bool $returnParams = false): null|array
    {
        try {

            $durMatch = Dur::where(['id' => $id])
                ->with(['keywords', 'team', 'sector'])
                ->first();

            $datasets = $durMatch->allDatasets  ?? [];

            $datasetIds = array_map(function ($dataset) {
                return $dataset['id'];
            }, $datasets);

            $durMatch = $durMatch->toArray();

            $datasetTitles = array();
            foreach ($datasetIds as $d) {
                $metadata = Dataset::where(['id' => $d])
                    ->first()
                    ->latestVersion()
                    ->metadata;
                $datasetTitles[] = $metadata['metadata']['summary']['shortTitle'];
            }

            $keywords = array();
            foreach ($durMatch['keywords'] as $k) {
                $keywords[] = $k['name'];
            }

            $sector = ($durMatch['sector'] != null) ? Sector::where(
                [
                    'id' => $durMatch['sector']
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
                'dataProvider' => $dataProvider
            ];

            $params = [
                'index' => ECC::ELASTIC_NAME_DUR,
                'id' => $id,
                'body' => $toIndex,
                'headers' => 'application/json'
            ];

            if($returnParams) {
                return $params;
            }

            ECC::indexDocument($params);
            return null;

        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Calls a re-indexing of Elastic search when a publication is created or updated
     *
     * @param string $id The publication id from the DB
     * @param bool $returnParams Optional flag to return parameters.
     *
     * @return null|array
     */
    public function indexElasticPublication(string $id, bool $returnParams = false): null|array
    {
        try {
            $pubMatch = Publication::where(['id' => $id])->first();
            $datasets = $pubMatch->allDatasets;

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

            foreach ($datasets as $dataset) {
                $metadata = Dataset::where(['id' => $dataset['id']])->first()->latestVersion()->metadata;
                $latestVersionID = Dataset::where(['id' => $dataset['id']])->first()->latestVersion()->id;
                $datasetTitles[] = $metadata['metadata']['summary']['shortTitle'];
                $linkType = PublicationHasDatasetVersion::where([
                    ['publication_id', '=', (int)$id],
                    ['dataset_version_id', '=', (int)$latestVersionID]
                ])->first()->link_type ?? 'UNKNOWN';
                $datasetLinkTypes[] =  array_key_exists($linkType, $linkTypeMappings) ? $linkTypeMappings[$linkType] : 'Unknown';
            }

            // Split string to array of strings
            $publicationTypes = explode(",", $pubMatch['publication_type']);

            // replace any empty strings with Research articles
            foreach ($publicationTypes as $i => $value) {
                if ($value === "") {
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
            ];
            $params = [
                'index' => ECC::ELASTIC_NAME_PUBLICATION,
                'id' => $id,
                'body' => $toIndex,
                'headers' => 'application/json'
            ];
            if($returnParams) {
                return $params;
            }

            ECC::indexDocument($params);
            return null;

        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Insert tool document into elastic index
     *
     * @param integer $toolId
     * @param bool $returnParams Optional flag to return parameters.
     * @return null|array
     */
    public function indexElasticTools(int $toolId, bool $returnParams = false): null|array
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
                ->with('versions')
                ->get();

            $dataProviderCollId = DataProviderCollHasTeam::where('team_id', $tool['team_id'])
                ->pluck('data_provider_coll_id')
                ->all();

            $dataProviderColl = DataProviderColl::whereIn('id', $dataProviderCollId)
                ->pluck('name')
                ->all();

            $datasetTitles = array();
            if ($tool->any_dataset) {
                $datasetTitles[] = '_Can be used with any dataset';
            } else {
                foreach ($datasets as $dataset) {
                    $dataset_version = $dataset['versions'][0];
                    $datasetTitles[] = $dataset_version['metadata']['metadata']['summary']['shortTitle'];
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
                'resultsInsights' => $tool['results_insights']
            ];

            $params = [
                'index' => ECC::ELASTIC_NAME_TOOL,
                'id' => $toolId,
                'body' => $toIndex,
                'headers' => 'application/json'
            ];

            if($returnParams) {
                return $params;
            }

            ECC::indexDocument($params);
            return null;

        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Reindex in bulk
     *
     * @param array $ids
     * @param callable $indexer Name of single index to call to get parameters
     * @return void
     */
    public function reindexElasticBulk(array $ids, callable $indexer): void
    {
        $bulkParams = [];
        foreach ($ids as $id) {
            $bulkParams[] = $indexer($id, true);
        }
        ECC::indexBulk($bulkParams);
        unset($bulkParams);
    }

    /**
     * Calls a delete on the document in ElasticSearch index when a dataset is
     * deleted
     *
     * @param string $id The id of the dataset to be deleted
     * @param string $indexType index type: dataset, publication
     *
     * @return void
     */
    public function deleteFromElastic(string $id, string $indexType): void
    {
        try {

            $params = [
                'index' => $indexType,
                'id' => $id,
                'headers' => 'application/json'
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

    public function deleteDataProviderCollFromElastic(string $id)
    {
        $this->deleteFromElastic($id, ECC::ELASTIC_NAME_DATAPROVIDERCOLL);
    }

    public function getMaterialTypes(array $metadata): array|null
    {
        $materialTypes = null;
        if(version_compare(Config::get('metadata.GWDM.version'), "2.0", "<")) {
            $containsTissue = !empty($this->getValueByPossibleKeys($metadata, [
                'metadata.coverage.biologicalsamples',
                'metadata.coverage.physicalSampleAvailability',
            ], ''));
        } else {
            $tissues =  Arr::get($metadata, 'metadata.tissuesSampleCollection', null);
            if (!is_null($tissues)) {
                $materialTypes = array_reduce($tissues, function ($return, $item) {
                    if ($item['materialType'] !== 'None/not available') {
                        $return[] = $item['materialType'];
                    }
                    return $return;
                }, []);
                $materialTypes = count($materialTypes) === 0 ? null : array_unique($materialTypes);
            }
        }
        return $materialTypes;
    }

    public function getContainsTissues(?array $materialTypes)
    {
        if($materialTypes === null) {
            return false;
        }
        return count($materialTypes) > 0;
    }

    /**
     * Insert tool document into elastic index
     *
     * @param integer $dataCustodianNetworkId
     * @param bool $returnParams Optional flag to return parameters.
     * @return null|array
     */
    public function indexElasticDataCustodianNetwork(int $dataCustodianNetworkId, bool $returnParams = false): null|array
    {
        try {
            $dpc = DataProviderColl::select('id', 'name', 'img_url', 'enabled', 'url', 'service', 'summary')
                ->with('teams')
                ->where([
                    'id' => $dataCustodianNetworkId,
                    'enabled' => 1,
            ])->first();

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
                'headers' => 'application/json'
            ];
            \Log::info(json_encode($params));

            if($returnParams) {
                return $params;
            }

            ECC::indexDocument($params);
            return null;
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    public function getInfoTeams(DataProviderColl $dp)
    {
        $idTeams = DataProviderCollHasTeam::where(['data_provider_coll_id' => $dp->id])->pluck('team_id')->toArray();
        $teamsResult = [];

        foreach ($idTeams as $idTeam) {
            $team = Team::select('id', 'name')->where(['id' => $idTeam])->first();
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
        $durIds = array_column($dataset->allDurs, 'id') ?? [];
        $collectionIds = array_column($dataset->allCollections, 'id') ?? [];
        $publicationIds = array_column($dataset->allPublications, 'id') ?? [];
        $toolIds = array_column($dataset->allTools, 'id') ?? [];

        $version = $dataset->latestVersion();
        $withLinks = DatasetVersion::where('id', $version['id'])
            ->with(['linkedDatasetVersions'])
            ->first();

        $dataset->setAttribute('versions', [$withLinks]);

        $metadataSummary = $dataset['versions'][0]['metadata']['metadata']['summary'] ?? [];

        $title = $this->getValueByPossibleKeys($metadataSummary, ['title'], '');
        $populationSize = $this->getValueByPossibleKeys($metadataSummary, ['populationSize'], -1);
        $datasetType = $this->getValueByPossibleKeys($metadataSummary, ['datasetType'], '');

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
            'collections' => $collectionIds
        ];

        return $datasetResources;
    }
}
