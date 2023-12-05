<?php

namespace Tests\Unit;

use Mauro;

use Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;



class MauroTest extends TestCase
{

    public static function mockCreateMauroData($json, $prefix = 'properties/') {
        $result = [];
    
        foreach ($json as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, self::mockCreateMauroData($value, $prefix . $key . '/'));
            } else {
                $result[] = [
                    'key' => $prefix . $key,
                    'value' => $value,
                    'id' => fake()->uuid(),
                ];
            }
        }
    
        return $result;
    }

    public static function mockedMauroCreateFolderResponse(string $label, string $description,string $parentFolderId): array
    {
        return [
            "id" => fake()->uuid(),
            "label" => $label,
            "lastUpdated" => fake()->iso8601(),
            "domainType" => "Folder",
            "hasChildFolders" => false,
            "readableByEveryone" => false,
            "readableByAuthenticatedUsers" => true,
            "availableActions" => [
                "comment",
                "delete",
                "editDescription",
                "save",
                "show",
                "softDelete",
                "update"
            ],
            "description" => $description
        ];
    }

    public static function mockedMauroCreateDatasetResponse(string $label, string $description, string $author, string $organisation, string $parentFolderId, array $jsonObj): array
    {
        $responseJson = [
                "id" => fake()->uuid(),
                "domainType" => "DataModel",
                "label" => $label,
                "description" => $description,
                "availableActions" => [
                    0 => "comment",
                    1 => "delete",
                    2 => "editDescription",
                    3 => "finalise",
                    4 => "save",
                    5 => "show",
                    6 => "softDelete",
                    7 => "update",
                ],
                "lastUpdated" => fake()->iso8601(),
                "type" => "Data Asset",
                "branchName" => "main",
                "documentationVersion" => "1.0.0",
                "finalised" => false,
                "readableByEveryone" => false,
                "readableByAuthenticatedUsers" => false,
                "author" => $author,
                "organisation" => $organisation,
                "authority" => [
                    "id" => fake()->uuid(),
                    "url" => fake()->url(),
                    "label" => "Mauro",
                    "defaultAuthority" => true,
                ]
        ];
    
        return [
                'DataModel' => [
                    'responseJson' => $responseJson,
                    'responseStatus' => 201,
                ]
            ];
    }

    public static function mockedFinaliseDataModel(string $datasetId){
        return [
            'id' => $datasetId,
            'documentationVersion' => fake()->randomNumber(1, 10),
        ];
    }

    private function mockedMauroGetFoldersByParentId(string $label,string $description, string $parentFolderId): array
    {
        return [
            "count" => 1,
            "items" => [
                [
                    "id" => $parentFolderId,
                    "label" => $label,
                    "description" => $description,
                    "lastUpdated" => fake()->iso8601(),
                    "domainType" => "Folder",
                    "hasChildFolders" => false,
                ]
            ]
        ];

    }
    public function test_it_can_create_and_delete_a_folder(): void
    {

        $postUrl = env('MAURO_API_URL');
        $parentFolderId = env('MAURO_PARENT_FOLDER_ID');
        $postUrl .= '/folders/' . $parentFolderId . '/folders';

        $label = 'Test Folder';
        $description = 'Automated Test - folder creation';
       
        Http::fake([
            $postUrl => Http::response($this->mockedMauroCreateFolderResponse($label,$description,$parentFolderId)
            , 200),
        ]);

        $jsonResponse = Mauro::createFolder(
            $label,
            $description,
            $parentFolderId,
        );

        $this->assertIsArray($jsonResponse);
        $this->assertArrayHasKey('id', $jsonResponse);

        $this->assertEquals('Test Folder', $jsonResponse['label']);
        $this->assertEquals('Automated Test - folder creation', $jsonResponse['description']);

        $createdFolderId = $jsonResponse['id'];

        $postUrl = env('MAURO_API_URL');
        $postUrl .= '/folders/' . $parentFolderId . '/folders/' . $createdFolderId . '?permanent=true';

        Http::fake([
            $postUrl => Http::response(true, 204),
        ]);

        $jsonResponse = Mauro::deleteFolder($createdFolderId, 'true', $parentFolderId);
        $this->assertEquals($jsonResponse, true);

    }

    public function test_it_can_create_and_delete_a_folder_under_a_parent(): void
    {

        $postUrl = env('MAURO_API_URL');
        $parentFolderId = env('MAURO_PARENT_FOLDER_ID');
        $postUrl .= '/folders/' . $parentFolderId . '/folders';

        $label = 'Test Folder ';
        $description = 'Automated Test - folder creation';
       
        Http::fake([
            $postUrl => Http::response($this->mockedMauroCreateFolderResponse($label,$description,$parentFolderId)
            , 200),
        ]);

        $jsonResponse = Mauro::createFolder(
            $label,
            $description,
            $parentFolderId
        );
       
        $this->assertIsArray($jsonResponse);
        $this->assertArrayHasKey('id', $jsonResponse);

        $parentFolderId = $jsonResponse['id'];

        $postUrl = env('MAURO_API_URL');
        $postUrl .= '/folders/' . $parentFolderId . '/folders';
        
        $label =  'Test Child Folder';


        Http::fake([
            $postUrl => 
                function (Request $request) use($label,$description,$parentFolderId) {
                    if ($request->method() == 'GET') {
                        return  Http::response($this->mockedMauroGetFoldersByParentId($label,$description,$parentFolderId),200);         
                    }
                    if ($request->method() == 'POST') {
                        return Http::response($this->mockedMauroCreateFolderResponse($label,$description,$parentFolderId),200);
                    }
                }
        ]);

        $jsonResponse = Mauro::createFolder(
            $label,
            $description,
            $parentFolderId
        );
        
        $this->assertIsArray($jsonResponse);
        $this->assertArrayHasKey('id', $jsonResponse);
    
        $getUrl = env('MAURO_API_URL');
        $getUrl .= '/folders/' . $parentFolderId . '/folders';

        $jsonResponse = Mauro::getFoldersByParentId($parentFolderId);

        
        $this->assertIsArray($jsonResponse);
        $this->assertEquals($jsonResponse['count'], 1);
        $this->assertEquals($jsonResponse['items'][0]['label'], 'Test Child Folder');

        $createdFolderId = $jsonResponse['items'][0]['id'];
        $deleteUrlChild = $postUrl . '/' . $createdFolderId . '?permanent=true';
        $deleteUrlParent = env('MAURO_API_URL') .'/folders/'.  $createdFolderId . '?permanent=true';

        Http::fake([
            $deleteUrlChild => Http::response(true, 204),
            $deleteUrlParent => Http::response(true, 204),
        ]);


        $jsonResponse = Mauro::deleteFolder($createdFolderId, 'true', $parentFolderId);
        $this->assertEquals($jsonResponse, true);

        $jsonResponse = Mauro::deleteFolder($parentFolderId);
        $this->assertEquals($jsonResponse, true);
    }

    public function test_it_can_create_and_delete_a_dataset(): void
    {
        // First read our test json metadata file
        $payload = file_get_contents('tests/Unit/test_files/gwdm_v1_dataset_min.json');
        $json = [
            'dataset' => json_decode($payload, true),
        ];

        // Secondly create a new folder (publisher) for this data model (dataset)
        $teamName = 'Test Parent Folder ' . fake()->regexify('[A-Z]{5}[0-4]{1}');
        $jsonResponse = Mauro::createFolder(
            $teamName,
            'Automated Test - Parent folder creation'
        );

        $this->assertIsArray($jsonResponse);
        $this->assertArrayHasKey('id', $jsonResponse);

        $parentFolderId = $jsonResponse['id'];

        // Finally, create the data model
        $label = 'Test Data Model ' . fake()->regexify('[A-Z]{5}[0-4]{1}');
        $jsonResponse = Mauro::createDataModel(
            $label,
            'Data Model Description',
            'A. Test',
            'Health Data Research UK',
            $parentFolderId,
            $json
        );

        $this->assertIsArray($jsonResponse);
        $this->assertArrayHasKey('DataModel', $jsonResponse);

        $this->assertEquals($jsonResponse['DataModel']['responseStatus'], 201);
        $this->assertEquals($jsonResponse['DataModel']['responseJson']['domainType'], 'DataModel');
        $this->assertEquals($jsonResponse['DataModel']['responseJson']['label'], $label);
        $this->assertEquals($jsonResponse['DataModel']['responseJson']['description'], 'Data Model Description');
        $this->assertEquals($jsonResponse['DataModel']['responseJson']['author'], 'A. Test');
        $this->assertEquals($jsonResponse['DataModel']['responseJson']['type'], 'Data Asset');

        $dataModelId = $jsonResponse['DataModel']['responseJson']['id'];

        $jsonResponse = Mauro::deleteDataModel($dataModelId, 'true');

        $this->assertEquals($jsonResponse, true);

        $jsonResponse = Mauro::deleteFolder($parentFolderId, 'true');
        $this->assertEquals($jsonResponse, true);
    }
}