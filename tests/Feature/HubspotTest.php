<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Services\Hubspot;
use Illuminate\Support\Facades\Http;
use Tests\Traits\MockExternalApis;

class HubspotTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    public function testCreateContact()
    {
        $hubspotService = new Hubspot();

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

        $response = $hubspotService->createContact($properties);

        $this->assertIsArray($response);
        $this->assertEquals(12345, $response['vid']);

        Http::assertSent(function ($request) use ($expectedBody) {
            return $request->url() == 'http://hub.local/contacts/v1/contact'
                && $request->method() == 'POST'
                && $request->hasHeader('Authorization', 'Bearer test_api_key')
                && $request->body() == json_encode($expectedBody);
        });
    }

    public function testUpdateContactById()
    {
        $hubspotService = new Hubspot();

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

        $response = $hubspotService->updateContactById($id, $properties);

        $this->assertEmpty($response);

        Http::assertSent(function ($request) use ($id, $expectedBody) {
            return $request->url() == "http://hub.local/contacts/v1/contact/vid/{$id}/profile"
                && $request->method() == 'POST'
                && $request->hasHeader('Authorization', 'Bearer test_api_key')
                && $request->body() == json_encode($expectedBody);
        });
    }

    public function testGetContactByEmail()
    {
        $hubspotService = new Hubspot();

        $email = 'john.doe@example.com';

        $response = $hubspotService->getContactByEmail($email);

        $this->assertEquals(12345, $response);

        Http::assertSent(function ($request) use ($email) {
            return $request->url() == "http://hub.local/contacts/v1/contact/email/{$email}/profile"
                && $request->method() == 'GET'
                && $request->hasHeader('Authorization', 'Bearer test_api_key');
        });
    }

    public function testGetContactById()
    {
        $hubspotService = new Hubspot();

        $id = 12345;

        $response = $hubspotService->getContactById($id);

        $this->assertIsArray($response);
        $this->assertEquals(12345, $response['vid']);

        Http::assertSent(function ($request) use ($id) {
            return $request->url() == "http://hub.local/contacts/v1/contact/vid/{$id}/profile"
                && $request->method() == 'GET'
                && $request->hasHeader('Authorization', 'Bearer test_api_key');
        });
    }

    public function testDeleteContactById()
    {
        $hubspotService = new Hubspot();

        $id = 12345;

        $response = $hubspotService->deleteContactById($id);

        $this->assertIsArray($response);

        Http::assertSent(function ($request) use ($id) {
            return $request->url() == "http://hub.local/contacts/v1/contact/vid/{$id}"
                && $request->method() == 'DELETE'
                && $request->hasHeader('Authorization', 'Bearer test_api_key');
        });
    }
}
