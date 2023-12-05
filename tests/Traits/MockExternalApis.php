<?php

namespace Tests\Traits;


use Database\Seeders\SectorSeeder;
use Illuminate\Support\Facades\Http;

use MetadataManagementController AS MMC;
use Mauro;

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Mock\Client;
use Nyholm\Psr7\Response;

use Tests\Unit\MauroTest;

trait MockExternalApis
{

    private $dataset = null;
    private $datasetUpdate = null;
    protected $header = [];

    private function getFakeDataset()
    {
        $jsonFile = file_get_contents(getcwd() . '/tests/Unit/test_files/gwdm_v1_dataset_min.json', 0, null);
        $json = json_decode($jsonFile, true);

        return $json;
    }


    private function getFakeUpdateDataset()
    {
        $jsonFile = file_get_contents(getcwd() . '/tests/Unit/test_files/gwdm_v1_dataset_min_update.json', 0, null);
        $json = json_decode($jsonFile, true);

        return $json;
    }
    
    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            SectorSeeder::class,
        ]);
        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];

        // Define mock client and fake response for elasticsearch service
        $mockElastic = new Client();

        $elasticClient = ClientBuilder::create()
            ->setHttpClient($mockElastic)
            ->build();

        // This is a PSR-7 response
        // Mock two responses, one for creating a dataset, another for deleting
        $createResponse = new Response(
            200, 
            [Elasticsearch::HEADER_CHECK => Elasticsearch::PRODUCT_NAME],
            'Document created'
        );
        $deleteResponse = new Response(
            200, 
            [Elasticsearch::HEADER_CHECK => Elasticsearch::PRODUCT_NAME],
            'Document deleted'
        );

        // Stack the responses expected in the create/archive/delete dataset test
        // create -> soft delete/archive -> unarchive -> permanent delete
        for ($i=0; $i < 100; $i++) {
            $mockElastic->addResponse($createResponse);
        }

        for ($i=0; $i < 100; $i++) {
            $mockElastic->addResponse($deleteResponse);
        }

        $this->testElasticClient = $elasticClient;

        Http::fake([
            'ted*' => Http::response(
                ['id' => 11, 'extracted_terms' => ['test', 'fake']], 
                201,
                ['application/json']
            )
        ]);
        
        // Mock the MMC getElasticClient method to return the mock client
        // makePartial so other MMC methods are not mocked
        MMC::shouldReceive('getElasticClient')->andReturn($this->testElasticClient);
        MMC::shouldReceive("translateDataModelType")->andReturnUsing(function(string $metadata){
            return [
                "traser_message" => "",
                "wasTranslated" => true,
                "metadata" => json_decode($metadata,true)["metadata"],
                "statusCode" => "200",
            ];
        });
        MMC::shouldReceive("validateDataModelType")->andReturn(true);
        MMC::makePartial();


        $this->dataset_store = [];
        $this->mauro_store = [];


        Mauro::shouldReceive('createFolder')->andReturnUsing(function (...$args){
            return MauroTest::mockedMauroCreateFolderResponse(...$args);
        });
        Mauro::shouldReceive('createDataModel')->andReturnUsing(function (...$args){
            $mauro = MauroTest::mockedMauroCreateDatasetResponse(...$args);
            $jsonObj = $args[count($args)-1];
            $id = $mauro["DataModel"]["responseJson"]["id"];
            
            $mauro_metadata = MauroTest::mockCreateMauroData($jsonObj['dataset']['metadata']);
            $this->mauro_store[$id] = $mauro_metadata;

            return $mauro;
        });
        Mauro::shouldReceive('updateDataModel')->andReturnUsing(function (...$args){
            $mauro = MauroTest::mockedMauroCreateDatasetResponse(...$args);
            $jsonObj = $args[count($args)-2];
            $id = $args[count($args)-1];
            $mauro_metadata = MauroTest::mockCreateMauroData($jsonObj['dataset']['metadata']);
            $this->mauro_store[$id] = $mauro_metadata;
    
            return $mauro;
        });
        Mauro::shouldReceive('finaliseDataModel')->andReturnUsing(function (string $datasetId){
            return MauroTest::mockedFinaliseDataModel($datasetId);
        });

        Mauro::shouldReceive('getDatasetByIdMetadata')->andReturnUsing(function (string $datasetId){
            $mauro_metadata = $this->mauro_store[$datasetId];
            return ["items" => $mauro_metadata];
        });

        Mauro::shouldReceive('getAllDataClasses')->andReturnUsing(function (string $datasetId){
            return ["items" => $this->mauro_store[$datasetId]];
        });

        Mauro::shouldReceive('deleteFolder')->andReturn(true);
        Mauro::shouldReceive('deleteDataModel')->andReturn(true);
        Mauro::shouldReceive('deleteDataClass')->andReturn(true);
        Mauro::shouldReceive('restoreDataModel')->andReturn(true);
       

        Mauro::shouldReceive('createDataClass')->andReturnUsing(function (...$args){
            return MauroTest::mockCreateDataClass(...$args);
        });

        Mauro::shouldReceive('createDataElement')->andReturnUsing(function (...$args){
            return MauroTest::mockCreateDataElement(...$args);
        });

        Mauro::shouldReceive('duplicateDataModel')->andReturnUsing(function (string $datasetId){
            return ["id"=>fake()->uuid()];
        });

        Mauro::makePartial();


    }

}