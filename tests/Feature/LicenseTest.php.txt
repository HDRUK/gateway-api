<?php

namespace Tests\Feature;

use App\Models\License;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;
use Database\Seeders\LicenseSeeder;
use Database\Seeders\MinimalUserSeeder;

class LicenseTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/licenses';

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            MinimalUserSeeder::class,
            LicenseSeeder::class,
        ]);
    }

    public function test_get_all_licenses_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                0 => [
                    'id',
                    'code',
                    'label',
                    'valid_since',
                    'valid_until',
                    'definition',
                    'verified',
                    'origin',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ],
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
    }

    public function test_get_license_by_id_with_success(): void
    {
        $licenceId = License::where('valid_until', null)->get()->random()->id;
        $response = $this->json('GET', self::TEST_URL . '/' .  $licenceId, [], $this->header);
        $license = License::where([
            'id' => (int) $licenceId,
        ])->first();

        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'code',
                'label',
                'valid_since',
                'valid_until',
                'definition',
                'verified',
                'origin',
                'created_at',
                'updated_at',
                'deleted_at',
            ]
        ])->assertStatus(200);
        $content = $response->decodeResponseJson();

        $this->assertEquals($content['data']['code'], $license->code);
        $this->assertEquals($content['data']['label'], $license->label);
        $this->assertEquals($content['data']['definition'], $license->definition);
    }

    public function test_create_license_with_success(): void
    {
        $payload = [
            'code' => 'TEST',
            'label' => 'test label',
            'valid_since' => '2013-11-26',
            'definition' => 'test definition',
            'origin' => 'HDR',
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL,
            $payload,
            $this->header,
        );

        $response->assertJsonStructure([
            'message',
            'data',
        ])->assertStatus(201);

        $licenseId = $response->decodeResponseJson()['data'];

        $license = License::where(['id' => $licenseId])->first();

        $this->assertEquals($license->code, $payload['code']);
        $this->assertEquals($license->label, $payload['label']);
        $this->assertEquals($license->definition, $payload['definition']);
    }

    public function test_update_license_with_success(): void
    {
        // create
        $payloadCreate = [
            'code' => 'TEST',
            'label' => 'test label',
            'valid_since' => '2013-11-26',
            'definition' => 'test definition',
            'origin' => 'HDR',
        ];

        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            $payloadCreate,
            $this->header,
        );

        $responseCreate->assertJsonStructure([
            'message',
            'data',
        ])->assertStatus(201);

        $licenseCreateId = $responseCreate->decodeResponseJson()['data'];

        $licenseCreate = License::where(['id' => $licenseCreateId])->first();

        $this->assertEquals($licenseCreate->code, $payloadCreate['code']);
        $this->assertEquals($licenseCreate->label, $payloadCreate['label']);
        $this->assertEquals($licenseCreate->definition, $payloadCreate['definition']);

        // update
        $payloadUpdate = [
            'code' => 'TEST_UPDATE',
            'label' => 'test label update',
            'valid_since' => '2013-11-26',
            'definition' => 'test definition update',
            'origin' => 'HDR',
        ];

        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $licenseCreateId,
            $payloadUpdate,
            $this->header,
        );

        $responseUpdate->assertJsonStructure([
            'message',
            'data',
        ])->assertStatus(200);

        $licenseUpdate = License::where(['id' => $licenseCreateId])->first();

        $this->assertEquals($licenseUpdate->code, $payloadUpdate['code']);
        $this->assertEquals($licenseUpdate->label, $payloadUpdate['label']);
        $this->assertEquals($licenseUpdate->definition, $payloadUpdate['definition']);
    }

    public function test_edit_license_with_success(): void
    {
        // create
        $payloadCreate = [
            'code' => 'TEST',
            'label' => 'test label',
            'valid_since' => '2013-11-26',
            'definition' => 'test definition',
            'origin' => 'HDR',
        ];

        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            $payloadCreate,
            $this->header,
        );

        $responseCreate->assertJsonStructure([
            'message',
            'data',
        ])->assertStatus(201);

        $licenseCreateId = $responseCreate->decodeResponseJson()['data'];

        $licenseCreate = License::where(['id' => $licenseCreateId])->first();

        $this->assertEquals($licenseCreate->code, $payloadCreate['code']);
        $this->assertEquals($licenseCreate->label, $payloadCreate['label']);
        $this->assertEquals($licenseCreate->definition, $payloadCreate['definition']);

        // update
        $payloadUpdate = [
            'code' => 'TEST_UPDATE',
            'label' => 'test label update',
            'valid_since' => '2013-11-26',
            'definition' => 'test definition update',
            'origin' => 'HDR',
        ];

        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $licenseCreateId,
            $payloadUpdate,
            $this->header,
        );

        $responseUpdate->assertJsonStructure([
            'message',
            'data',
        ])->assertStatus(200);

        $licenseUpdate = License::where(['id' => $licenseCreateId])->first();

        $this->assertEquals($licenseUpdate->code, $payloadUpdate['code']);
        $this->assertEquals($licenseUpdate->label, $payloadUpdate['label']);
        $this->assertEquals($licenseUpdate->definition, $payloadUpdate['definition']);

        // edit
        $payloadEdit = [
            'code' => 'TEST_EDIT',
            'label' => 'test label edit',
            'definition' => 'test definition edit',
        ];

        $responseEdit = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $licenseCreateId,
            $payloadEdit,
            $this->header,
        );

        $responseEdit->assertJsonStructure([
            'message',
            'data',
        ])->assertStatus(200);

        $licenseEdit = License::where(['id' => $licenseCreateId])->first();

        $this->assertEquals($licenseEdit->code, $payloadEdit['code']);
        $this->assertEquals($licenseEdit->label, $payloadEdit['label']);
        $this->assertEquals($licenseEdit->definition, $payloadEdit['definition']);
    }

    public function test_delete_license_with_success(): void
    {
        // create
        $payloadCreate = [
            'code' => 'TEST',
            'label' => 'test label',
            'valid_since' => '2013-11-26',
            'definition' => 'test definition',
            'origin' => 'HDR',
        ];

        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            $payloadCreate,
            $this->header,
        );

        $responseCreate->assertJsonStructure([
            'message',
            'data',
        ])->assertStatus(201);

        $licenseCreateId = $responseCreate->decodeResponseJson()['data'];

        $licenseCreate = License::where(['id' => $licenseCreateId])->first();

        $this->assertEquals($licenseCreate->code, $payloadCreate['code']);
        $this->assertEquals($licenseCreate->label, $payloadCreate['label']);
        $this->assertEquals($licenseCreate->definition, $payloadCreate['definition']);

        // update
        $payloadUpdate = [
            'code' => 'TEST_UPDATE',
            'label' => 'test label update',
            'valid_since' => '2013-11-26',
            'definition' => 'test definition update',
            'origin' => 'HDR',
        ];

        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $licenseCreateId,
            $payloadUpdate,
            $this->header,
        );

        $responseUpdate->assertJsonStructure([
            'message',
            'data',
        ])->assertStatus(200);

        $licenseUpdate = License::where(['id' => $licenseCreateId])->first();

        $this->assertEquals($licenseUpdate->code, $payloadUpdate['code']);
        $this->assertEquals($licenseUpdate->label, $payloadUpdate['label']);
        $this->assertEquals($licenseUpdate->definition, $payloadUpdate['definition']);

        // edit
        $payloadEdit = [
            'code' => 'TEST_EDIT',
            'label' => 'test label edit',
            'definition' => 'test definition edit',
        ];

        $responseEdit = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $licenseCreateId,
            $payloadEdit,
            $this->header,
        );

        $responseEdit->assertJsonStructure([
            'message',
            'data',
        ])->assertStatus(200);

        $licenseEdit = License::where(['id' => $licenseCreateId])->first();

        $this->assertEquals($licenseEdit->code, $payloadEdit['code']);
        $this->assertEquals($licenseEdit->label, $payloadEdit['label']);
        $this->assertEquals($licenseEdit->definition, $payloadEdit['definition']);

        // delete
        $responseEdit = $this->json(
            'DELETE',
            self::TEST_URL . '/' . $licenseCreateId,
            [],
            $this->header,
        );

        $licenseEdit = License::where(['id' => $licenseCreateId])->first();

        $this->assertTrue(!$licenseEdit, 'response is null');
    }
}
