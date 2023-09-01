<?php

namespace Tests\Unit;

use Mauro;

use Tests\TestCase;
use Illuminate\Support\Facades\Facade;

class MauroTest extends TestCase
{
    public function test_it_can_list_folders(): void
    {
        $jsonResponse = Mauro::getFolders();

        $this->assertIsArray($jsonResponse);
        $this->assertGreaterThan(0, (int)$jsonResponse['count']);
        
        $keyCheck = [
            'id',
            'label',
            'lastUpdated',
            'domainType',
            'hasChildFolders',
        ];

        foreach ($keyCheck as $key) {
            $this->assertArrayHasKey($key, $jsonResponse['items'][0]);
        }
    }

    public function test_it_can_list_folders_by_id(): void
    {
        $jsonResponse = Mauro::getFolders();

        $this->assertIsArray($jsonResponse);
        $this->assertGreaterThan(0, (int)$jsonResponse['count']);

        $jsonResponse = Mauro::getFolderById($jsonResponse['items'][0]['id']);

        $this->assertIsArray($jsonResponse);

        $keyCheck = [
            'id',
            'label',
            'lastUpdated',
            'domainType',
            'hasChildFolders',
            'readableByEveryone',
            'readableByAuthenticatedUsers',
            'availableActions',
            'description',
        ];

        foreach ($keyCheck as $key) {
            $this->assertArrayHasKey($key, $jsonResponse);
        }
    }

    public function test_it_can_create_and_delete_a_folder(): void
    {
        $jsonResponse = Mauro::createFolder(
            'Test Folder',
            'Automated Test - folder creation'
        );

        $this->assertIsArray($jsonResponse);
        $this->assertArrayHasKey('id', $jsonResponse);

        $this->assertEquals('Test Folder', $jsonResponse['label']);
        $this->assertEquals('Automated Test - folder creation', $jsonResponse['description']);

        $createdFolderId = $jsonResponse['id'];

        $jsonResponse = Mauro::deleteFolder($createdFolderId, 'true', '');
        $this->assertEquals($jsonResponse, true);

    }

    public function test_it_can_create_and_delete_a_folder_under_a_parent(): void
    {
        $jsonResponse = Mauro::createFolder(
            'Test Parent Folder',
            'Automated Test - Parent folder creation'
        );

        $this->assertIsArray($jsonResponse);
        $this->assertArrayHasKey('id', $jsonResponse);

        $parentFolderId = $jsonResponse['id'];

        $jsonResponse = Mauro::createFolder(
            'Test Child Folder',
            'Automated Test - Child folder creation',
            $parentFolderId
        );

        $this->assertIsArray($jsonResponse);
        $this->assertArrayHasKey('id', $jsonResponse);

        $childFolderId = $jsonResponse['id'];

        $jsonResponse = Mauro::getFoldersByParentId($parentFolderId);
        
        $this->assertIsArray($jsonResponse);
        $this->assertEquals($jsonResponse['count'], 1);
        $this->assertEquals($jsonResponse['items'][0]['label'], 'Test Child Folder');

        $jsonResponse = Mauro::deleteFolder($jsonResponse['items'][0]['id'], 'true', $parentFolderId);
        $this->assertEquals($jsonResponse, true);

        $jsonResponse = Mauro::deleteFolder($parentFolderId);
        $this->assertEquals($jsonResponse, true);
    }

    public function test_it_can_create_and_delete_a_dataset(): void
    {
        // First read our test json metadata file
        $payload = file_get_contents('tests/Unit/test_files/mauro_test_dataset.json');

        // Secondly create a new folder (publisher) for this data model (dataset)
        $jsonResponse = Mauro::createFolder(
            'Test Parent Folder',
            'Automated Test - Parent folder creation'
        );

        $this->assertIsArray($jsonResponse);
        $this->assertArrayHasKey('id', $jsonResponse);

        $parentFolderId = $jsonResponse['id'];

        // Finally, create the data model
        $jsonResponse = Mauro::createDataModel(
            'Test Data Model',
            'Data Model Description',
            'A. Test',
            'Health Data Research UK',
            $parentFolderId,
            json_decode($payload, true)
        );

        $this->assertIsArray($jsonResponse);
        $this->assertArrayHasKey('DataModel', $jsonResponse);

        $this->assertEquals($jsonResponse['DataModel']['responseStatus'], 201);
        $this->assertEquals($jsonResponse['DataModel']['responseJson']['domainType'], 'DataModel');
        $this->assertEquals($jsonResponse['DataModel']['responseJson']['label'], 'Test Data Model');
        $this->assertEquals($jsonResponse['DataModel']['responseJson']['description'], 'Data Model Description');
        $this->assertEquals($jsonResponse['DataModel']['responseJson']['author'], 'A. Test');
        $this->assertEquals($jsonResponse['DataModel']['responseJson']['type'], 'Data Asset');

        $dataModelId = $jsonResponse['DataModel']['responseJson']['id'];

        $jsonResponse = Mauro::deleteDataModel($dataModelId);
        $this->assertEquals($jsonResponse, true);

        $jsonResponse = Mauro::deleteFolder($parentFolderId, 'true');
        $this->assertEquals($jsonResponse, true);
    }
}