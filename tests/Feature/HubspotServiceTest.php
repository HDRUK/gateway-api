<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Services\HubspotService;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HubspotServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::shouldReceive('get')
            ->with('services.hubspot.base_url')
            ->andReturn('https://api.hubapi.com');

        Config::shouldReceive('get')
            ->with('services.hubspot.key')
            ->andReturn('test_api_key');
    }

    public function testCreateContact()
    {
        $hubspotService = new HubspotService();

        $properties = [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe@example.com',
        ];

        $expectedBody = [
            'properties' => [
                ['property' => 'firstname', 'value' => 'John'],
                ['property' => 'lastname', 'value' => 'Doe'],
                ['property' => 'email', 'value' => 'john.doe@example.com'],
            ],
        ];

        Http::fake([
            'https://api.hubapi.com/contacts/v1/contact' => Http::response(['vid' => 12345], 200),
        ]);

        $response = $hubspotService->createContact($properties);

        $this->assertIsArray($response);
        $this->assertEquals(12345, $response['vid']);

        Http::assertSent(function ($request) use ($expectedBody) {
            return $request->url() == 'https://api.hubapi.com/contacts/v1/contact'
                && $request->method() == 'POST'
                && $request->hasHeader('Authorization', 'Bearer test_api_key')
                && $request->body() == json_encode($expectedBody);
        });
    }

    public function testUpdateContactById()
    {
        $hubspotService = new HubspotService();

        $id = 12345;
        $properties = [
            'firstname' => 'John',
            'lastname' => 'Doe',
        ];

        $expectedBody = [
            'properties' => [
                ['property' => 'firstname', 'value' => 'John'],
                ['property' => 'lastname', 'value' => 'Doe'],
            ],
        ];

        Http::fake([
            "https://api.hubapi.com/contacts/v1/contact/vid/{$id}/profile" => Http::response([], 204),
        ]);

        $response = $hubspotService->updateContactById($id, $properties);

        $this->assertEmpty($response);

        Http::assertSent(function ($request) use ($id, $expectedBody) {
            return $request->url() == "https://api.hubapi.com/contacts/v1/contact/vid/{$id}/profile"
                && $request->method() == 'POST'
                && $request->hasHeader('Authorization', 'Bearer test_api_key')
                && $request->body() == json_encode($expectedBody);
        });
    }

    public function testGetContactByEmail()
    {
        $hubspotService = new HubspotService();

        $email = 'john.doe@example.com';

        Http::fake([
            "https://api.hubapi.com/contacts/v1/contact/email/{$email}/profile" => Http::response(['vid' => 12345], 200),
        ]);

        $response = $hubspotService->getContactByEmail($email);

        $this->assertEquals(12345, $response);

        Http::assertSent(function ($request) use ($email) {
            return $request->url() == "https://api.hubapi.com/contacts/v1/contact/email/{$email}/profile"
                && $request->method() == 'GET'
                && $request->hasHeader('Authorization', 'Bearer test_api_key');
        });
    }

    public function testGetContactById()
    {
        $hubspotService = new HubspotService();

        $id = 12345;

        Http::fake([
            "https://api.hubapi.com/contacts/v1/contact/vid/{$id}/profile" => Http::response(['vid' => 12345, 'properties' => []], 200),
        ]);

        $response = $hubspotService->getContactById($id);

        $this->assertIsArray($response);
        $this->assertEquals(12345, $response['vid']);

        Http::assertSent(function ($request) use ($id) {
            return $request->url() == "https://api.hubapi.com/contacts/v1/contact/vid/{$id}/profile"
                && $request->method() == 'GET'
                && $request->hasHeader('Authorization', 'Bearer test_api_key');
        });
    }

    public function testDeleteContactById()
    {
        $hubspotService = new HubspotService();

        $id = 12345;

        Http::fake([
            "https://api.hubapi.com/contacts/v1/contact/vid/{$id}" => Http::response([], 200),
        ]);

        $response = $hubspotService->deleteContactById($id);

        $this->assertIsArray($response);

        Http::assertSent(function ($request) use ($id) {
            return $request->url() == "https://api.hubapi.com/contacts/v1/contact/vid/{$id}"
                && $request->method() == 'DELETE'
                && $request->hasHeader('Authorization', 'Bearer test_api_key');
        });
    }
}
