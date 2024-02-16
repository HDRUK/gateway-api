<?php

namespace App\MetadataManagementController;

use Config;
use Mauro;
use Exception;

use App\Models\Filter;
use App\Models\Dataset;
use App\Models\DatasetVersion;

use App\Exceptions\MMCException;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

use Illuminate\Support\Facades\DB;

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

            $urlString = env('TRASER_SERVICE_URL') . '/translate?' . http_build_query($queryParams);

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
                env('TRASER_SERVICE_URL'),
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
     * Calls a re-indexing of Elastic search when a dataset is created, updated or added to a collection
     * 
     * @param string $datasetId The dataset id from the DB
     * 
     * @return void
     */
    public function reindexElastic(string $datasetId): void
    {
        // Get named entities
        try {

            $datasetMatch = Dataset::where(['id' => $datasetId])
                ->with(['namedEntities', 'collections'])
                ->first();
            
            $version = $datasetMatch->latestVersion();
            $metadata = $version->metadata;
            $dataset = $datasetMatch->toArray();

            $namedEntities = array();
            foreach ($dataset['named_entities'] as $n) {
                $namedEntities[] = $n['name'];
            }

            $collections = array();
            foreach ($dataset['collections'] as $c) {
                $collections[] = $c['name'];
            }


            $metadataModelVersion = $metadata['gwdmVersion'];

            // ------------------------------------------------------
            // WARNING....
            //  - this part of the code may need updating when the GWDM is changed 
            //  - can we make this more dynamic in someway?
            // ------------------------------------------------------
            $publisherName = '';
            $physicalSampleAvailability = [];

            if(version_compare($metadataModelVersion,"1.1","<")){
                $publisherName = $metadata['metadata']['summary']['publisher']['publisherName'];
                $physicalSampleAvailability = explode(',', $metadata['metadata']['coverage']['physicalSampleAvailability']);
            } else {
                if (array_key_exists('name',$metadata['metadata']['summary']['publisher'])){
                    $publisherName = $metadata['metadata']['summary']['publisher']['name'];
                }
                if(array_key_exists('biologicalsamples',$metadata['metadata']['coverage'])){
                    $physicalSampleAvailability = explode(',', $metadata['metadata']['coverage']['biologicalsamples']);
                }
            }
            
            $toIndex = [
                'abstract' => $metadata['metadata']['summary']['abstract'],
                'keywords' => $metadata['metadata']['summary']['keywords'],
                'description' => $metadata['metadata']['summary']['description'],
                'shortTitle' => $metadata['metadata']['summary']['shortTitle'],
                'title' => $metadata['metadata']['summary']['title'],
                'publisherName' => $publisherName,
                'startDate' => $metadata['metadata']['provenance']['temporal']['startDate'],
                'endDate' => $metadata['metadata']['provenance']['temporal']['endDate'],
                'physicalSampleAvailability' => $physicalSampleAvailability,
                'conformsTo' => explode(',', $metadata['metadata']['accessibility']['formatAndStandards']['conformsTo']),
                'hasTechnicalMetadata' => (bool) $datasetMatch['has_technical_details'],
                'named_entities' => $namedEntities,
                'collections' => $collections
            ];
            

            $params = [
                'index' => 'dataset',
                'id' => $datasetMatch['id'],
                'body' => $toIndex,
                'headers' => 'application/json'
            ];
            
            $client = $this->getElasticClient();
            $response = $client->index($params);

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
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
}
