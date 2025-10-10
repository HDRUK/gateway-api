<?php

namespace Tests\Feature;

// 
use Config;
use Tests\TestCase;
use App\Models\DarIntegration;
use App\Models\Application;
use App\Models\Permission;
use App\Models\ApplicationHasPermission;
use Database\Seeders\DarIntegrationSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\ApplicationSeeder;

use Tests\Traits\MockExternalApis;

class DarIntegrationTest extends TestCase
{
    
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL_DAR = '/api/v1/integrations/dar';

    private $integration = null;

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            MinimalUserSeeder::class,
            ApplicationSeeder::class,
            DarIntegrationSeeder::class,
        ]);

        $this->integration = Application::where('id', 1)->first();

        $perms = Permission::whereIn('name', [
            'dar.read.all',
            'dar.read.assigned',
            'dar.update',
            'dar.decision',
        ])->get();

        foreach ($perms as $perm) {
            // Use firstOrCreate ignoring the return as we only care that missing perms
            // of the above are added, rather than retrieving existing
            ApplicationHasPermission::firstOrCreate([
                'application_id' => $this->integration->id,
                'permission_id' => $perm->id,
            ]);
        }

        // Add Integration auth keys to the header generated in commonSetUp
        $this->header['x-application-id'] = $this->integration['app_id'];
        $this->header['x-client-id'] = $this->integration['client_id'];

    }

    /**
     * List all DARs.
     *
     * @return void
     */
    public function test_the_application_can_list_dars()
    {
        $response = $this->json('GET', self::TEST_URL_DAR, [], $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'enabled',
                        'notification_email',
                        'outbound_auth_type',
                        'outbound_auth_key',
                        'outbound_endpoints_base_url',
                        'outbound_endpoints_enquiry',
                        'outbound_endpoints_5safes',
                        'outbound_endpoints_5safes_files',
                        'inbound_service_account_id',
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

    /**
     * Returns a single DAR
     *
     * @return void
     */
    public function test_the_application_can_list_a_single_dar()
    {
        $dar = DarIntegration::all()->first();
        $response = $this->json('GET', self::TEST_URL_DAR . '/' . $dar->id, [], $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'enabled',
                    'notification_email',
                    'outbound_auth_type',
                    'outbound_auth_key',
                    'outbound_endpoints_base_url',
                    'outbound_endpoints_enquiry',
                    'outbound_endpoints_5safes',
                    'outbound_endpoints_5safes_files',
                    'inbound_service_account_id',
                ],
            ]);

    }

    // REMOVED - Perm Matrix doesn't allow application created DAR
    // /**
    //  * Creates a new DAR
    //  *
    //  * @return void
    //  */
    // public function test_the_application_can_create_a_dar()
    // {
    //     $response = $this->json(
    //         'POST',
    //         self::TEST_URL_DAR,
    //         [
    //             'enabled' => 1,
    //             'notification_email' => 'someone@somewhere.com',
    //             'outbound_auth_type' => 'auth_type_123',
    //             'outbound_auth_key' => 'auth_key_456',
    //             'outbound_endpoints_base_url' => 'https://something.com/',
    //             'outbound_endpoints_enquiry' => 'enquiry',
    //             'outbound_endpoints_5safes' => '5safes',
    //             'outbound_endpoints_5safes_files' => '5safes-files',
    //             'inbound_service_account_id' => '1234567890',
    //         ],
    //         $this->header,
    //     );

    //     $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
    //         ->assertJsonStructure([
    //             'message',
    //             'data',
    //         ]);

    //     $content = $response->decodeResponseJson();
    //     $this->assertEquals($content['message'],
    //         Config::get('statuscodes.STATUS_CREATED.message'));
    // }

    /**
     * Tests that a DAR record can be updated
     *
     * @return void
     */
    public function test_the_application_can_update_a_dar()
    {
        $dar = DarIntegration::all()->first();

        $response = $this->json(
            'PUT',
            self::TEST_URL_DAR . '/' . $dar->id,
            [
                'enabled' => 1,
                'notification_email' => 'someone.else@somewhere-else.com',
                'outbound_auth_type' => 'auth_type_234',
                'outbound_auth_key' => 'auth_key_123',
                'outbound_endpoints_base_url' => 'https://something.com/',
                'outbound_endpoints_enquiry' => 'enquiry',
                'outbound_endpoints_5safes' => '5safes',
                'outbound_endpoints_5safes_files' => '5safes-files',
                'inbound_service_account_id' => '0987654321',
            ],
            $this->header,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['data']['notification_email'], 'someone.else@somewhere-else.com');
        $this->assertEquals($content['data']['outbound_auth_type'], 'auth_type_234');
        $this->assertEquals($content['data']['outbound_auth_key'], 'auth_key_123');
        $this->assertEquals($content['data']['inbound_service_account_id'], '0987654321');
    }

    /**
     * Tests that a DAR record can be updated
     *
     * @return void
     */
    public function test_the_application_can_edit_a_dar()
    {
        $dar = DarIntegration::all()->first();

        // update
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL_DAR . '/' . $dar->id,
            [
                'enabled' => 1,
                'notification_email' => 'someone.else@somewhere-else.com',
                'outbound_auth_type' => 'auth_type_234',
                'outbound_auth_key' => 'auth_key_123',
                'outbound_endpoints_base_url' => 'https://something.com/',
                'outbound_endpoints_enquiry' => 'enquiry',
                'outbound_endpoints_5safes' => '5safes',
                'outbound_endpoints_5safes_files' => '5safes-files',
                'inbound_service_account_id' => '0987654321',
            ],
            $this->header,
        );

        $responseUpdate->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $contentUpdate = $responseUpdate->decodeResponseJson();
        $this->assertEquals($contentUpdate['data']['notification_email'], 'someone.else@somewhere-else.com');
        $this->assertEquals($contentUpdate['data']['outbound_auth_type'], 'auth_type_234');
        $this->assertEquals($contentUpdate['data']['outbound_auth_key'], 'auth_key_123');
        $this->assertEquals($contentUpdate['data']['inbound_service_account_id'], '0987654321');

        // edit
        $responseEdit1 = $this->json(
            'PATCH',
            self::TEST_URL_DAR . '/' . $dar->id,
            [
                'outbound_auth_type' => 'auth_type_234_e1',
                'outbound_auth_key' => 'auth_key_123_e1',
                'outbound_endpoints_base_url' => 'https://something.e1.com/',
                'outbound_endpoints_enquiry' => 'enquiry_e1',
                'outbound_endpoints_5safes' => '5safes_e1',
                'outbound_endpoints_5safes_files' => '5safes-files-e1',
            ],
            $this->header,
        );

        $responseEdit1->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentEdit1 = $responseEdit1->decodeResponseJson();
        $this->assertEquals($contentEdit1['data']['outbound_auth_type'], 'auth_type_234_e1');
        $this->assertEquals($contentEdit1['data']['outbound_auth_key'], 'auth_key_123_e1');
        $this->assertEquals($contentEdit1['data']['outbound_endpoints_base_url'], 'https://something.e1.com/');
        $this->assertEquals($contentEdit1['data']['outbound_endpoints_enquiry'], 'enquiry_e1');
        $this->assertEquals($contentEdit1['data']['outbound_endpoints_5safes'], '5safes_e1');
        $this->assertEquals($contentEdit1['data']['outbound_endpoints_5safes_files'], '5safes-files-e1');

        // Edit
        $responseEdit2 = $this->json(
            'PATCH',
            self::TEST_URL_DAR . '/' . $dar->id,
            [
                'notification_email' => 'someone.else@somewhere-e2.com',
                'outbound_auth_type' => 'auth_type_234_e2',
            ],
            $this->header,
        );

        $responseEdit2->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentEdit2 = $responseEdit2->decodeResponseJson();
        $this->assertEquals($contentEdit2['data']['notification_email'], 'someone.else@somewhere-e2.com');
        $this->assertEquals($contentEdit2['data']['outbound_auth_type'], 'auth_type_234_e2');
    }

    // REMOVED - Perms Matrix doesn't allow application delete of DAR
    // /**
    //  * Tests it can delete a DAR
    //  *
    //  * @return void
    //  */
    // public function test_it_can_delete_a_dar()
    // {
    //     // Start by creating a new DAR record for deleting
    //     // within this test case
    //     $response = $this->json(
    //         'POST',
    //         self::TEST_URL_DAR,
    //         [
    //             'enabled' => 1,
    //             'notification_email' => 'someone@somewhere.com',
    //             'outbound_auth_type' => 'auth_type_123',
    //             'outbound_auth_key' => 'auth_key_456',
    //             'outbound_endpoints_base_url' => 'https://something.com/',
    //             'outbound_endpoints_enquiry' => 'enquiry',
    //             'outbound_endpoints_5safes' => '5safes',
    //             'outbound_endpoints_5safes_files' => '5safes-files',
    //             'inbound_service_account_id' => '1234567890',
    //         ],
    //         $this->header,
    //     );

    //     $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
    //         ->assertJsonStructure([
    //             'message',
    //             'data',
    //         ]);

    //     $content = $response->decodeResponseJson();
    //     $this->assertEquals($content['message'],
    //         Config::get('statuscodes.STATUS_CREATED.message')
    //     );

    //     // Finally, delete the last entered DAR to
    //     // prove functionality
    //     $response = $this->json(
    //         'DELETE',
    //         self::TEST_URL_DAR . '/' . $content['data'],
    //         [],
    //         $this->header,
    //     );

    //     $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
    //         ->assertJsonStructure([
    //             'message',
    //         ]);

    //     $content = $response->decodeResponseJson();
    //     $this->assertEquals($content['message'],
    //         Config::get('statuscodes.STATUS_OK.message'));
    // }
}
