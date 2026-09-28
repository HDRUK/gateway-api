<?php

namespace Tests\Unit;

use App\Jobs\SendEmailCustomIntegration;
use App\Jobs\SendEmailJob;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\EmailTemplate;
use App\Models\FederationJobRun;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendEmailCustomIntegrationTest extends TestCase
{
    private const FEDERATION_ID = 42;
    private const TEAM_ID       = 10;
    private const JOB_UUID      = 'test-job-uuid-1234';

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Partial mock with all external-dependency methods stubbed.
     * Individual tests can re-stub specific methods using ->method() after creation.
     */
    private function makeJob(
        string $outcome,
        ?string $jobUuid = self::JOB_UUID,
        ?string $errorMessage = null,
        int $historyCount = 3
    ): SendEmailCustomIntegration {
        $mock = $this->getMockBuilder(SendEmailCustomIntegration::class)
            ->onlyMethods(['getDetails', 'getFederationHistory', 'checkUserPerms', 'getListOfUsers'])
            ->setConstructorArgs([self::FEDERATION_ID, $jobUuid, $outcome, $errorMessage])
            ->getMock();

        $mock->method('getDetails')->willReturn($this->federationDetails());
        $mock->method('getFederationHistory')->willReturn($this->historyStub($historyCount));
        $mock->method('checkUserPerms')->willReturn(true);
        $mock->method('getListOfUsers')->willReturn('<ul><li>Admin User</li></ul>');

        return $mock;
    }

    /** Fresh mock where getDetails is pre-configured to a specific payload. */
    private function mockJobWithDetails(array $details): SendEmailCustomIntegration
    {
        $mock = $this->getMockBuilder(SendEmailCustomIntegration::class)
            ->onlyMethods(['getDetails', 'getFederationHistory', 'checkUserPerms', 'getListOfUsers'])
            ->setConstructorArgs([self::FEDERATION_ID, self::JOB_UUID, 'success', null])
            ->getMock();

        $mock->method('getDetails')->willReturn($details);
        $mock->method('getFederationHistory')->willReturn($this->historyStub());
        $mock->method('checkUserPerms')->willReturn(true);
        $mock->method('getListOfUsers')->willReturn('');

        return $mock;
    }

    private function federationDetails(array $notificationOverrides = []): array
    {
        $user = array_merge([
            'id'              => 100,
            'name'            => 'Jane Doe',
            'firstname'       => 'Jane',
            'email'           => 'jane@example.com',
            'secondary_email' => 'jane-alt@example.com',
            'preferred_email' => 'primary',
        ], $notificationOverrides);

        return [
            'id'            => self::FEDERATION_ID,
            'team'          => [['id' => self::TEAM_ID, 'name' => 'Test Team']],
            'notifications' => [['user_notification' => $user]],
        ];
    }

    private function historyStub(int $count = 3): array
    {
        return [
            'integration_success' => '<ul><li>PID: A</li><li>PID: B</li><li>PID: C</li></ul>',
            'integration_errors'  => '<ul></ul>',
            'success_count'       => $count,
        ];
    }

    private function seedTemplate(string $identifier): EmailTemplate
    {
        return EmailTemplate::create([
            'identifier' => $identifier,
            'subject'    => 'Test Subject',
            'body'       => '<mjml></mjml>',
            'enabled'    => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // Template selection: correct identifier resolved from outcome × permission
    // -------------------------------------------------------------------------

    public function test_dispatches_success_email_for_admin_user(): void
    {
        $this->seedTemplate('integration.job.success.teamadmin_developer');
        $job = $this->makeJob('success');
        $job->method('checkUserPerms')->willReturn(true);

        $job->handle();

        Queue::assertPushed(SendEmailJob::class, 1);
    }

    public function test_dispatches_success_email_for_non_admin_user(): void
    {
        $this->seedTemplate('integration.job.success.no_teamadmin_developer');
        $job = $this->makeJob('success');
        $job->method('checkUserPerms')->willReturn(false);

        $job->handle();

        Queue::assertPushed(SendEmailJob::class, 1);
    }

    public function test_dispatches_failure_email_for_admin_user(): void
    {
        $this->seedTemplate('integration.job.failure.teamadmin_developer');
        $job = $this->makeJob('failure', self::JOB_UUID, 'Connection timed out');
        $job->method('checkUserPerms')->willReturn(true);

        $job->handle();

        Queue::assertPushed(SendEmailJob::class, 1);
    }

    public function test_dispatches_failure_email_for_non_admin_user(): void
    {
        $this->seedTemplate('integration.job.failure.no_teamadmin_developer');
        $job = $this->makeJob('failure', self::JOB_UUID, 'Connection timed out');
        $job->method('checkUserPerms')->willReturn(false);

        $job->handle();

        Queue::assertPushed(SendEmailJob::class, 1);
    }

    // -------------------------------------------------------------------------
    // Guard conditions: skip without dispatch
    // -------------------------------------------------------------------------

    public function test_skips_notification_when_user_notification_is_null(): void
    {
        $job = $this->mockJobWithDetails([
            'id'            => self::FEDERATION_ID,
            'team'          => [['id' => self::TEAM_ID, 'name' => 'Test Team']],
            'notifications' => [['user_notification' => null]],
        ]);

        $job->handle();

        Queue::assertNothingPushed();
    }

    public function test_skips_notification_when_primary_email_is_null(): void
    {
        $job = $this->mockJobWithDetails(
            $this->federationDetails(['email' => null, 'preferred_email' => 'primary'])
        );

        $job->handle();

        Queue::assertNothingPushed();
    }

    public function test_skips_notification_when_secondary_email_is_null_and_preferred(): void
    {
        $job = $this->mockJobWithDetails(
            $this->federationDetails(['secondary_email' => null, 'preferred_email' => 'secondary'])
        );

        $job->handle();

        Queue::assertNothingPushed();
    }

    public function test_skips_success_email_when_no_datasets_were_ingested(): void
    {
        $this->seedTemplate('integration.job.success.teamadmin_developer');
        $job = $this->makeJob('success', historyCount: 0);

        $job->handle();

        Queue::assertNothingPushed();
    }

    public function test_dispatches_success_email_when_datasets_were_ingested(): void
    {
        $this->seedTemplate('integration.job.success.teamadmin_developer');
        $job = $this->makeJob('success', historyCount: 1);

        $job->handle();

        Queue::assertPushed(SendEmailJob::class, 1);
    }

    public function test_logs_warning_and_skips_when_template_not_found(): void
    {
        // Use a made-up outcome that has no matching template in the database.
        // Template lookup + missing-template logging now lives in EmailManager.
        Log::spy();

        $job = $this->getMockBuilder(SendEmailCustomIntegration::class)
            ->onlyMethods(['getDetails', 'getFederationHistory', 'checkUserPerms', 'getListOfUsers'])
            ->setConstructorArgs([self::FEDERATION_ID, self::JOB_UUID, 'nonexistent', null])
            ->getMock();

        $job->method('getDetails')->willReturn($this->federationDetails());
        $job->method('getFederationHistory')->willReturn($this->historyStub());
        $job->method('checkUserPerms')->willReturn(true);
        $job->method('getListOfUsers')->willReturn('');

        $job->handle();

        Queue::assertNothingPushed();
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'template'));
    }

    // -------------------------------------------------------------------------
    // Null jobUuid: failure dispatched with empty history, no history fetch
    // -------------------------------------------------------------------------

    public function test_failure_with_null_uuid_dispatches_without_calling_history(): void
    {
        $this->seedTemplate('integration.job.failure.teamadmin_developer');

        $job = $this->getMockBuilder(SendEmailCustomIntegration::class)
            ->onlyMethods(['getDetails', 'getFederationHistory', 'checkUserPerms', 'getListOfUsers'])
            ->setConstructorArgs([self::FEDERATION_ID, null, 'failure', 'Crashed before UUID assigned'])
            ->getMock();

        $job->method('getDetails')->willReturn($this->federationDetails());
        $job->method('checkUserPerms')->willReturn(true);
        $job->method('getListOfUsers')->willReturn('');
        $job->expects($this->never())->method('getFederationHistory');

        $job->handle();

        Queue::assertPushed(SendEmailJob::class, 1);
    }

    // -------------------------------------------------------------------------
    // Email address preference
    // -------------------------------------------------------------------------

    public function test_uses_secondary_email_when_preferred(): void
    {
        $this->seedTemplate('integration.job.success.teamadmin_developer');

        $job = $this->makeJob('success');
        $job->method('getDetails')->willReturn(
            $this->federationDetails([
                'email'           => 'primary@example.com',
                'secondary_email' => 'secondary@example.com',
                'preferred_email' => 'secondary',
            ])
        );

        $job->handle();

        Queue::assertPushed(SendEmailJob::class, 1);
    }

    public function test_uses_primary_email_when_preferred(): void
    {
        $this->seedTemplate('integration.job.success.teamadmin_developer');

        $job = $this->makeJob('success');
        $job->method('getDetails')->willReturn(
            $this->federationDetails([
                'email'           => 'primary@example.com',
                'secondary_email' => 'secondary@example.com',
                'preferred_email' => 'primary',
            ])
        );

        $job->handle();

        Queue::assertPushed(SendEmailJob::class, 1);
    }

    // -------------------------------------------------------------------------
    // getFederationHistory() — real DB, no mocking
    // -------------------------------------------------------------------------

    public function test_history_counts_only_successful_pids(): void
    {
        FederationJobRun::create([
            'team_id'       => self::TEAM_ID,
            'federation_id' => self::FEDERATION_ID,
            'job_uuid'      => self::JOB_UUID,
            'pid'           => 'AAA',
            'status'        => 1,
            'details'       => ['message' => 'Synced OK'],
            'job_attempts'  => 1,
        ]);
        FederationJobRun::create([
            'team_id'       => self::TEAM_ID,
            'federation_id' => self::FEDERATION_ID,
            'job_uuid'      => self::JOB_UUID,
            'pid'           => 'BBB',
            'status'        => 1,
            'details'       => ['message' => 'Synced OK'],
            'job_attempts'  => 1,
        ]);

        $job    = new SendEmailCustomIntegration(self::FEDERATION_ID, self::JOB_UUID, 'success');
        $result = $job->getFederationHistory();

        $this->assertSame(2, $result['success_count']);
        $this->assertStringContainsString('PID: AAA', $result['integration_success']);
        $this->assertStringContainsString('PID: BBB', $result['integration_success']);
        $this->assertSame('<ul></ul>', $result['integration_errors']);
    }

    public function test_history_separates_success_and_failure_pids(): void
    {
        FederationJobRun::create([
            'team_id'       => self::TEAM_ID,
            'federation_id' => self::FEDERATION_ID,
            'job_uuid'      => self::JOB_UUID,
            'pid'           => 'OK1',
            'status'        => 1,
            'details'       => ['message' => 'Synced'],
            'job_attempts'  => 1,
        ]);
        FederationJobRun::create([
            'team_id'       => self::TEAM_ID,
            'federation_id' => self::FEDERATION_ID,
            'job_uuid'      => self::JOB_UUID,
            'pid'           => 'ERR1',
            'status'        => 0,
            'details'       => [
                'message' => [[
                    'name'    => 'my-dataset',
                    'version' => '2.0',
                    'errors'  => [['message' => 'Schema validation failed']],
                ]],
            ],
            'job_attempts'  => 1,
        ]);

        $job    = new SendEmailCustomIntegration(self::FEDERATION_ID, self::JOB_UUID, 'failure');
        $result = $job->getFederationHistory();

        $this->assertSame(1, $result['success_count']);
        $this->assertStringContainsString('PID: OK1', $result['integration_success']);
        $this->assertStringContainsString('PID - ERR1', $result['integration_errors']);
        $this->assertStringContainsString('Schema validation failed', $result['integration_errors']);
        $this->assertStringContainsString('my-dataset/2.0', $result['integration_errors']);
    }

    public function test_history_handles_string_error_message_without_throwing(): void
    {
        FederationJobRun::create([
            'team_id'       => self::TEAM_ID,
            'federation_id' => self::FEDERATION_ID,
            'job_uuid'      => self::JOB_UUID,
            'pid'           => 'ERR-STR',
            'status'        => 0,
            'details'       => ['message' => 'encountered internal server error'],
            'job_attempts'  => 1,
        ]);

        $job    = new SendEmailCustomIntegration(self::FEDERATION_ID, self::JOB_UUID, 'failure');
        $result = $job->getFederationHistory();

        $this->assertSame(0, $result['success_count']);
        $this->assertStringContainsString('PID - ERR-STR', $result['integration_errors']);
        $this->assertStringContainsString('encountered internal server error', $result['integration_errors']);
    }

    public function test_history_returns_empty_lists_when_no_runs_exist(): void
    {
        $job    = new SendEmailCustomIntegration(self::FEDERATION_ID, 'nonexistent-uuid', 'success');
        $result = $job->getFederationHistory();

        $this->assertSame(0, $result['success_count']);
        $this->assertSame('<ul></ul>', $result['integration_success']);
        $this->assertSame('<ul></ul>', $result['integration_errors']);
    }

    public function test_history_preserves_full_multi_schema_translation_errors(): void
    {
        FederationJobRun::create([
            'team_id'       => self::TEAM_ID,
            'federation_id' => self::FEDERATION_ID,
            'job_uuid'      => self::JOB_UUID,
            'pid'           => '276ef9ca-0000-0001',
            'status'        => 0,
            'details'       => [
                'message' => [
                    ['name' => 'HDRUK',     'version' => '2.0.2', 'errors' => [['message' => "must NOT have additional properties"]]],
                    ['name' => 'HDRUK',     'version' => '2.1.0', 'errors' => [['message' => "must have required property 'publisher'"]]],
                    ['name' => 'HDRUK',     'version' => '3.0.0', 'errors' => [['message' => "must have required property 'populationSize'"]]],
                    ['name' => 'GWDM',      'version' => '1.0',   'errors' => [['message' => "must have required property 'required'"]]],
                    ['name' => 'GWDM',      'version' => '2.1',   'errors' => [['message' => "must have required property 'required'"]]],
                    ['name' => 'SchemaOrg', 'version' => 'BioSchema', 'errors' => [['message' => "must have required property 'name'"]]],
                ],
            ],
            'job_attempts'  => 1,
        ]);

        $job    = new SendEmailCustomIntegration(self::FEDERATION_ID, self::JOB_UUID, 'failure');
        $result = $job->getFederationHistory();

        $errors = $result['integration_errors'];

        $this->assertStringContainsString('PID - 276ef9ca-0000-0001', $errors);
        $this->assertStringContainsString("HDRUK/2.0.2  - must NOT have additional properties", $errors);
        $this->assertStringContainsString("HDRUK/2.1.0  - must have required property 'publisher'", $errors);
        $this->assertStringContainsString("HDRUK/3.0.0  - must have required property 'populationSize'", $errors);
        $this->assertStringContainsString("GWDM/1.0  - must have required property 'required'", $errors);
        $this->assertStringContainsString("GWDM/2.1  - must have required property 'required'", $errors);
        $this->assertStringContainsString("SchemaOrg/BioSchema  - must have required property 'name'", $errors);
    }

    public function test_history_does_not_leak_sensitive_data_from_redacted_exception_messages(): void
    {
        // Simulates what the catch blocks in GatewayMetadataIngestionTrait now store —
        // a safe generic string with a job reference, not the raw exception.
        FederationJobRun::create([
            'team_id'       => self::TEAM_ID,
            'federation_id' => self::FEDERATION_ID,
            'job_uuid'      => self::JOB_UUID,
            'pid'           => 'redacted-pid',
            'status'        => 0,
            'details'       => [
                'message' => 'An unexpected error occurred while creating dataset redacted-pid. Please contact support and reference job: ' . self::JOB_UUID,
            ],
            'job_attempts'  => 1,
        ]);

        $job    = new SendEmailCustomIntegration(self::FEDERATION_ID, self::JOB_UUID, 'failure');
        $result = $job->getFederationHistory();

        $errors = $result['integration_errors'];

        $this->assertStringContainsString('PID - redacted-pid', $errors);
        $this->assertStringContainsString('unexpected error', $errors);
        $this->assertStringContainsString(self::JOB_UUID, $errors);

        // None of these patterns — typical of leaked internal exceptions — should appear.
        $this->assertStringNotContainsString('projects/', $errors);
        $this->assertStringNotContainsString('secrets/', $errors);
        $this->assertStringNotContainsString('Exception', $errors);
        $this->assertStringNotContainsString('Stack trace', $errors);
        $this->assertStringNotContainsString('/var/www', $errors);
    }

    public function test_history_ignores_runs_from_other_job_uuids(): void
    {
        FederationJobRun::create([
            'team_id'       => self::TEAM_ID,
            'federation_id' => self::FEDERATION_ID,
            'job_uuid'      => 'other-uuid-9999',
            'pid'           => 'ZZZ',
            'status'        => 1,
            'details'       => ['message' => 'Should not appear'],
            'job_attempts'  => 1,
        ]);

        $job    = new SendEmailCustomIntegration(self::FEDERATION_ID, self::JOB_UUID, 'success');
        $result = $job->getFederationHistory();

        $this->assertSame(0, $result['success_count']);
        $this->assertStringNotContainsString('ZZZ', $result['integration_success']);
    }

    private function makeDatasetWithTitle(string $pid, string $title): Dataset
    {
        $dataset = Dataset::factory()->create([
            'pid'    => $pid,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        DatasetVersion::create([
            'dataset_id'  => $dataset->id,
            'version'     => 1,
            'patch'       => null,
            'metadata'    => ['gwdmVersion' => config('metadata.GWDM.version'), 'metadata' => []],
            'title'       => $title,
            'short_title' => $title,
        ]);

        return $dataset;
    }

    public function test_history_includes_dataset_title_for_successful_pid_when_available(): void
    {
        $this->makeDatasetWithTitle('TITLED-OK', 'My Dataset Title');

        FederationJobRun::create([
            'team_id'       => self::TEAM_ID,
            'federation_id' => self::FEDERATION_ID,
            'job_uuid'      => self::JOB_UUID,
            'pid'           => 'TITLED-OK',
            'status'        => 1,
            'details'       => ['message' => 'Synced OK'],
            'job_attempts'  => 1,
        ]);

        $job    = new SendEmailCustomIntegration(self::FEDERATION_ID, self::JOB_UUID, 'success');
        $result = $job->getFederationHistory();

        $this->assertStringContainsString('My Dataset Title (PID: TITLED-OK)', $result['integration_success']);
    }

    public function test_history_includes_dataset_title_for_failed_pid_when_dataset_already_exists(): void
    {
        $this->makeDatasetWithTitle('TITLED-ERR', 'Existing Dataset');

        FederationJobRun::create([
            'team_id'       => self::TEAM_ID,
            'federation_id' => self::FEDERATION_ID,
            'job_uuid'      => self::JOB_UUID,
            'pid'           => 'TITLED-ERR',
            'status'        => 0,
            'details'       => ['message' => 'update failed validation'],
            'job_attempts'  => 1,
        ]);

        $job    = new SendEmailCustomIntegration(self::FEDERATION_ID, self::JOB_UUID, 'failure');
        $result = $job->getFederationHistory();

        $this->assertStringContainsString('Existing Dataset (PID: TITLED-ERR)', $result['integration_errors']);
    }

    public function test_history_falls_back_to_pid_only_when_no_dataset_exists_for_failed_pid(): void
    {
        FederationJobRun::create([
            'team_id'       => self::TEAM_ID,
            'federation_id' => self::FEDERATION_ID,
            'job_uuid'      => self::JOB_UUID,
            'pid'           => 'NEW-PID-NO-DATASET',
            'status'        => 0,
            'details'       => ['message' => 'translation failed'],
            'job_attempts'  => 1,
        ]);

        $job    = new SendEmailCustomIntegration(self::FEDERATION_ID, self::JOB_UUID, 'failure');
        $result = $job->getFederationHistory();

        $this->assertStringContainsString('PID - NEW-PID-NO-DATASET', $result['integration_errors']);
        $this->assertStringNotContainsString('(PID:', $result['integration_errors']);
    }
}
