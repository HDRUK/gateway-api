<?php

namespace App\MetadataManagementController;

use Mauro;
use Config;
use Exception;
use App\Exceptions\MMCException;
use App\Models\Filter;
use App\Models\Dataset;
use App\Models\DataProviderColl;
use App\Models\DataProviderCollHasTeam;
use App\Models\DatasetVersion;
use App\Models\Team;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Http\Client\HttpClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MetadataManagementController {

    /**
     * Configures and builds the client used to reindex ElasticSearch
     * Note: this client is defined here in the MMC facade, making it convenient
     * to mock during testing
     * 
     * @return Client
     */
    public function getElasticClient() {
        return ClientBuilder::create()
            ->setHosts(config('database.connections.elasticsearch.hosts'))
            ->setSSLVerification(env('ELASTICSEARCH_VERIFY_SSL'))
            ->setBasicAuthentication(env('ELASTICSEARCH_USER'), env('ELASTICSEARCH_PASS'))
            ->build();
    }
    
    /**
     * Translates an incoming dataset payload via TRASER 
     * from $inputSchema and $inputVersion to $outputSchema and
     * $outputVersion
     * 
     * @param string $dataset The incoming dataset to validate
     * 
     * @return array
     */
    public function translateDataModelType(
        string $dataset,
        string $outputSchema,
        string $outputVersion,
        string $inputSchema = null,
        string $inputVersion = null,
        bool $validateInput = true,
        bool $validateOutput = true,
        ): array
    {
        try {
            
            $queryParams = [
                'output_schema' => $outputSchema,
                'output_version' => $outputVersion,
                'input_schema' => $inputSchema,
                'input_version' => $inputVersion,
                'validate_input' => $validateInput ? "1" : 0 ,
                'validate_output' => $validateOutput ? "1" : 0 ,
            ];

            $urlString = env('TRASER_SERVICE_URL', 'http://localhost:8002') . '/translate?' . http_build_query($queryParams);

            // !! Dragons ahead !!
            // Suggest that no one change this, ever. Took hours
            // to debug. Laravel's usual ::post facade explicitly
            // sets application/json content-type, but for some
            // reason the data was being tampered with and the
            // traser service couldn't validate the body. This
            // ::withBody is an old school call that does *exactly*
            // the same thing, but for some reason, this works
            // whereas ::post does not?!
            // 
            // TODO: Needs further investigation. Enigma alert.
            $response = Http::withBody(
                $dataset, 'application/json'
            )->post($urlString);

            $wasTranslated =  $response->status() === 200;
            $metadata = null;
            $message = null;
            if($wasTranslated){
                $metadata = $response->json();
                $message = 'translation successful';
            }
            else{
                $message = $response->json();
            }

            return array(
                'traser_message' => $message,
                'wasTranslated' => $wasTranslated,
                'metadata' => $metadata,
                'statusCode' => $response->status(),
            );

        } catch (Exception $e) {
            throw new MMCException($e->getMessage());
        }
    }

    /**
     * Attempts to validate that the passed $dataset is in
     * GWDM format
     * 
     * @param string $dataset The incoming dataset to validate
     * @param string $input_schema The schema to validate against
     * @param string $input_version The schema version to validate against
     * 
     * @return bool
     */
    public function validateDataModelType(string $dataset, string $input_schema, string $input_version): bool
    {
        try {
            $urlString = sprintf("%s/validate?input_schema=%s&input_version=%s",
                env('TRASER_SERVICE_URL', 'http://localhost:8002'),
                $input_schema,
                $input_version
            );

            // !! Dragons ahead !!
            // Suggest that no one change this, ever. Took hours
            // to debug. Laravel's usual ::post facade explicitly
            // sets application/json content-type, but for some
            // reason the data was being tampered with and the
            // traser service couldn't validate the body. This
            // ::withBody is an old school call that does *exactly*
            // the same thing, but for some reason, this works
            // whereas ::post does not?!.
            // 
            // TODO: Needs further investigation. Enigma alert.
            $response = Http::withBody(
                $dataset, 'application/json'
            )->post($urlString);

            return ($response->status() === 200);
        } catch (Exception $e) {
            throw new MMCException($e->getMessage());
        }
    }

    /**
     * Creates an instance of a dataset record within the database
     * 
     * @param array $input The array object that makes up the metadata
     *  to store
     * 
     * @return Dataset
     */
    public function createDataset(array $input): Dataset
    {
        return Dataset::create($input);
    }

    public function createDatasetVersion(array $input): DatasetVersion
    {
        return DatasetVersion::create($input);
    }

    /**
     * (Soft) Deletes a dataset from the system by $id
     * 
     * @param string $id The dataset to delete
     * 
     * @return void
     */
    public function deleteDataset(string $id): void
    {
        try {
            $dataset = Dataset::with('versions')->where('id', (int)$id)->first();
            if(!$dataset){
                throw new Exception('Dataset with id='.$id." cannot be found");
            }
            $dataset->deleted_at = Carbon::now();
            $dataset->status = Dataset::STATUS_ARCHIVED;
            $dataset->save();

            foreach ($dataset->versions as $metadata) {
                $metadata->deleted_at = Carbon::now();
                $metadata->save();
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Calls a re-indexing of Elastic search when a dataset is created, updated or added to a collection.
     * 
     * @param string $datasetId The dataset id from the DB.
     * 
     * @return void
     */
    public function reindexElastic(string $datasetId): void
    {
        try {
            $datasetMatch = Dataset::where('id', $datasetId)
                ->with(['durs'])
                ->firstOrFail();

            $metadata = $datasetMatch->latestVersion()->metadata;

            // inject relationships via datasetVersionss
            $namedEntities = $datasetMatch->getLatestNamedEntities();
            $spatialCoverage = $datasetMatch->getLatestSpatialCoverage();
            $collections = $datasetMatch->getLatestCollections();
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
                'endDate' => $this->getValueByPossibleKeys($metadata, ['metadata.provenance.temporal.endDate'], Carbon::now()->addYears(180)),
                'dataType' => $this->getValueByPossibleKeys($metadata, ['metadata.summary.datasetType'], ''),
                'containsTissue' => $containsTissue,
                'sampleAvailability' => $materialTypes,
                'conformsTo' => explode(',', $this->getValueByPossibleKeys($metadata, ['metadata.accessibility.formatAndStandards.conformsTo'], '')),
                'hasTechnicalMetadata' => (bool) count($this->getValueByPossibleKeys($metadata, ['metadata.structuralMetadata'], [])),
                'named_entities' =>  $namedEntities->pluck('name')->all(),
                'collectionName' => $collections->pluck('name')->all(),
                'dataUseTitles' => $datasetMatch->durs->pluck('project_title')->all(),
                'geographicLocation' => $spatialCoverage->pluck('region')->all(),
                'accessService' => $this->getValueByPossibleKeys($metadata, ['metadata.accessibility.access.accessServiceCategory'], ''),
                'dataProviderColl' => DataProviderColl::whereIn('id', DataProviderCollHasTeam::where('team_id', $datasetMatch->team_id)->pluck('data_provider_coll_id'))->pluck('name')->all(),
            ];

            $params = [
                'index' => 'dataset',
                'id' => $datasetMatch->id,
                'body' => $toIndex,
                'headers' => 'application/json'
            ];

            $client = $this->getElasticClient();
            $client->index($params);

        } catch (Exception $e) {
            \Log::error('Error reindexing ElasticSearch', [
                'datasetId' => $datasetId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Calls a re-indexing of Elastic search when a data procider id is given.
     * 
     * @param string $teamId The team id from the DB.
     * 
     * @return void
     */
    public function reindexElasticDataProvider(string $teamId): void
    {
        try {
            $datasets = Dataset::where('team_id', $teamId) ->get();

            $datasetTitles = array();
            $locations = array();
            $dataTypes = array();
            foreach ($datasets as $dataset) {
                $dataset->setAttribute('spatialCoverage', $dataset->getLatestSpatialCoverage());
                $metadata = $dataset->latestVersion()->metadata;
                $datasetTitles[] = $metadata['metadata']['summary']['shortTitle'];
                if (!in_array($metadata['metadata']['summary']['datasetType'], $dataTypes)) {
                    $dataTypes[] = $metadata['metadata']['summary']['datasetType'];
                }
                foreach ($dataset['spatialCoverage'] as $loc) {
                    if (!in_array($loc['region'], $locations)) {
                        $locations[] = $loc['region'];
                    }
                }  
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

            $client = $this->getElasticClient();
            $client->index($params);

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
     * Search for a value in an array by trying multiple possible keys in order.
     *
     * @param array $array The array to search.
     * @param array $keys The list of possible keys to try, in order.
     * @param mixed $default The default value to return if none of the keys are found.
     * @return mixed The value of the first key found, or the default value if none are found.
     */
    public function getValueByPossibleKeys(array $array, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            $value = Arr::get($array, $key, null);
            if (!is_null($value)) {
                return $value;
            }
        }
        Log::info('No value found for any of the specified keys', ['keys' => $keys, 'array' => $array]);
        return $default;
    }

    /**
     * Calls a delete on the document in ElasticSearch index when a dataset is 
     * deleted
     * 
     * @param string $id The id of the dataset to be deleted
     * 
     * @return void
     */
    public function deleteFromElastic(string $id): void
    {
        try {

            $params = [
                'index' => 'dataset',
                'id' => $id,
                'headers' => 'application/json'
            ];
            
            $client = $this->getElasticClient();
            $response = $client->delete($params);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getOnboardingFormHydrated(string $name, string $version, string $dataTypes): array
    {
        try {
            $queryParams = [
                'name' => $name,
                'version' => $version,
                'dataTypes' => $dataTypes
            ];

            $urlString = env('TRASER_SERVICE_URL') . '/get/form_hydration?' . http_build_query($queryParams);
            $response = Http::get($urlString);

            return $response->json();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getMaterialTypes(array $metadata): array|null
    {
        $materialTypes = null;
        if(version_compare(Config::get('metadata.GWDM.version'),"2.0","<")){
            $containsTissue = !empty($this->getValueByPossibleKeys($metadata, ['metadata.coverage.biologicalsamples', 'metadata.coverage.physicalSampleAvailability'], ''));
        }
        else{
            $tissues =  Arr::get($metadata, 'metadata.tissuesSampleCollection', null);
            if (!is_null($tissues)) {
                $materialTypes = array_map(function ($item) {
                    return $item['materialType'];
                }, $tissues);
            } 
        }
        return $materialTypes;
    }

    public function getContainsTissues(?array $materialTypes){
        if($materialTypes === null){
            return false;
        }
        return count($materialTypes) > 0;
    }
}
