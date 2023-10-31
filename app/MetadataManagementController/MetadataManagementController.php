<?php

namespace App\MetadataManagementController;

use Config;
use Mauro;
use Exception;

use App\Models\Dataset;

use App\Exceptions\MMCException;

use Illuminate\Support\Facades\Http;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

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

    public function createMauroDataModel(array $user, array $team, array $input): array
    {
        return Mauro::createDataModel(
            $input['label'],
            $input['short_description'],
            $user['name'],
            $team['name'],
            $team['mdm_folder_id'],
            $input
        );
    }

    public function updateDataModel(array $user, array $team, array $input, string $dataModelId): array
    {
        return Mauro::updateDataModel(
            $input['label'],
            $input['short_description'],
            $user['name'],
            $team['name'],
            $team['mdm_folder_id'],
            $input,
            $dataModelId
        );
    }

    /**
     * Calls a re-indexing of Elastic search when a dataset is created or updated
     * 
     * @param array $dataset The dataset being created or updated
     * @param string $datasetId The dataset id from Mauro
     * 
     * @return void
     */
    public function reindexElastic(array $dataset, string $datasetId): void
    {
        // Get named entities
        try {

            $datasetMatch = Dataset::where('datasetid', $datasetId)
                ->with(['namedEntities'])
                ->first()
                ->toArray();

            $namedEntities = array();
            foreach ($datasetMatch['named_entities'] as $n) {
                $namedEntities[] = $n['name'];
            }

            $toIndex = [
                'abstract' => $dataset['summary']['abstract'],
                'keywords' => $dataset['summary']['keywords'],
                'description' => $dataset['summary']['description'],
                'shortTitle' => $dataset['summary']['shortTitle'],
                'title' => $dataset['summary']['title'],
                'publisher_name' => $dataset['summary']['publisher']['publisherName'],
                'named_entities' => $namedEntities
            ];

            $params = [
                'index' => 'datasets',
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
                'index' => 'datasets',
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