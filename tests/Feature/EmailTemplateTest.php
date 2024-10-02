<?php

namespace Tests\Feature;

use App\Models\EmailTemplate;
use Tests\TestCase;
use Database\Seeders\EmailTemplateSeeder;
use Tests\Traits\MockExternalApis;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmailTemplateTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/emailtemplates';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            EmailTemplateSeeder::class,
        ]);
    }

    /**
     * Get All Email Templates with success
     *
     * @return void
     */
    public function test_get_all_email_templates_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            0 => [
                'id',
                'created_at',
                'updated_at',
                'deleted_at',
                'identifier',
                'enabled',
                'body',
                'subject',
            ],
        ]);
    }

    /**
     * Get Email Template by Id with success
     *
     * @return void
     */
    public function test_get_email_template_by_id_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL . '/1', [], $this->header);
        $this->assertCount(1, $response['data']);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'identifier',
                    'enabled',
                    'body',
                    'subject',
                ]
            ]
        ]);
        $response->assertStatus(200);
    }

    /**
     * Create new Email Template with success
     *
     * @return void
     */
    public function test_add_new_email_template_with_success(): void
    {
        $body = [
            'identifier' => 'identifier email template test',
            'subject' => 'subject email template test',
            'body' => '<mjml><mj-body>test</mj-body></mjml>',
            'enabled' => true,
        ];
        $response = $this->json(
            'POST',
            self::TEST_URL . '/',
            $body,
            $this->header
        );

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data',
        ]);

        $emailTemplate = EmailTemplate::where('identifier', $body['identifier'])
            ->where('subject', $body['subject'])
            ->where('body', $body['body'])
            ->where('enabled', $body['enabled'])
            ->exists();

        $this->assertTrue((bool) $emailTemplate, 'Response was successfully');
    }

    /**
     * Update Email Template with success
     *
     * @return void
     */
    public function test_update_email_template_with_success(): void
    {
        // create email template
        $bodyCreate = [
            'identifier' => 'identifier email template test',
            'subject' => 'subject email template test',
            'body' => '<mjml><mj-body>test</mj-body></mjml>',
            'enabled' => true,
        ];
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL . '/',
            $bodyCreate,
            $this->header
        );

        $responseCreate->assertStatus(201);
        $responseCreate->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $id = $contentCreate['data'];

        $emailTemplateCreate = EmailTemplate::where('identifier', $bodyCreate['identifier'])
            ->where('subject', $bodyCreate['subject'])
            ->where('body', $bodyCreate['body'])
            ->where('enabled', $bodyCreate['enabled'])
            ->exists();

        $this->assertTrue((bool) $emailTemplateCreate, 'Response was successfully');

        // update email template
        $bodyUpdate = [
            'identifier' => 'identifier email template update',
            'subject' => 'subject email template update',
            'body' => '<mjml><mj-body>update</mj-body></mjml>',
            'enabled' => true,
        ];
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $id,
            $bodyUpdate,
            $this->header
        );
        $responseUpdate->assertStatus(200);
        $responseUpdate->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'created_at',
                'updated_at',
                'deleted_at',
                'identifier',
                'enabled',
                'body',
                'subject',
            ]
        ]);
        $emailTemplateUpdate = EmailTemplate::where('identifier', $bodyUpdate['identifier'])
            ->where('subject', $bodyUpdate['subject'])
            ->where('body', $bodyUpdate['body'])
            ->where('enabled', $bodyUpdate['enabled'])
            ->where('id', $id)
            ->exists();

        $this->assertTrue((bool) $emailTemplateUpdate, 'Response was successfully');
    }

    /**
     * Edit Email Template with success
     *
     * @return void
     */
    public function test_edit_email_template_with_success(): void
    {
        // create email template
        $bodyCreate = [
            'identifier' => 'identifier email template test',
            'subject' => 'subject email template test',
            'body' => '<mjml><mj-body>test</mj-body></mjml>',
            'enabled' => true,
        ];
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL . '/',
            $bodyCreate,
            $this->header
        );

        $responseCreate->assertStatus(201);
        $responseCreate->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $id = $contentCreate['data'];

        $emailTemplateCreate = EmailTemplate::where('identifier', $bodyCreate['identifier'])
            ->where('subject', $bodyCreate['subject'])
            ->where('body', $bodyCreate['body'])
            ->where('enabled', $bodyCreate['enabled'])
            ->exists();

        $this->assertTrue((bool) $emailTemplateCreate, 'Response was successfully');

        // edit email template
        $bodyEdit = [
            'identifier' => 'identifier email template edit',
            'subject' => 'subject email template edit',
            'body' => '<mjml><mj-body>update</mj-body></mjml>',
        ];
        $responseEdit = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $id,
            $bodyEdit,
            $this->header
        );

        $responseEdit->assertStatus(200);
        $responseEdit->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'created_at',
                'updated_at',
                'deleted_at',
                'identifier',
                'enabled',
                'body',
                'subject',
            ]
        ]);
        $emailTemplateEdit = EmailTemplate::where('identifier', $bodyEdit['identifier'])
            ->where('subject', $bodyEdit['subject'])
            ->where('body', $bodyEdit['body'])
            ->where('id', $id)
            ->exists();

        $this->assertTrue((bool) $emailTemplateEdit, 'Response was successfully');
    }

    /**
     * Delete Email Template with success
     *
     * @return void
     */
    public function test_delete_email_template_with_success(): void
    {
        // create email template
        $bodyCreate = [
            'identifier' => 'identifier email template test',
            'subject' => 'subject email template test',
            'body' => '<mjml><mj-body>test</mj-body></mjml>',
            'enabled' => true,
        ];
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL . '/',
            $bodyCreate,
            $this->header
        );

        $responseCreate->assertStatus(201);
        $responseCreate->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $id = $contentCreate['data'];

        $emailTemplateCreate = EmailTemplate::where('identifier', $bodyCreate['identifier'])
        ->where('subject', $bodyCreate['subject'])
        ->where('body', $bodyCreate['body'])
        ->where('enabled', $bodyCreate['enabled'])
        ->exists();

        $this->assertTrue((bool) $emailTemplateCreate, 'Response was successfully');

        // edit email template
        $responseDelete = $this->json(
            'DELETE',
            self::TEST_URL . '/' . $id,
            [],
            $this->header
        );
        $responseDelete->assertStatus(200);
        $responseDelete->assertJsonStructure([
            'message',
        ]);
        $emailTemplateDelete = EmailTemplate::where('id', $id)->exists();

        $this->assertFalse((bool) $emailTemplateDelete, 'Response was successfully');
    }
}
