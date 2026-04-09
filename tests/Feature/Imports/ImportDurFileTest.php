<?php

namespace Tests\Feature\Imports;

use App\Imports\ImportDurFile;
use App\Models\Dur;
use Mockery;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class ImportDurFileTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private array $data = [
        'user_id' => 1,
        'team_id' => 1,
    ];

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    private function validateHeaders(): array
    {
        return [
            0 => 'Project ID',
            1 => 'Organisation name*',
            2 => 'Organisation ID',
            3 => 'Organisation sector',
            4 => 'Applicant name(s)',
            5 => 'Applicant ID',
            6 => 'Funders/ Sponsors',
            7 => 'DEA accredited researcher?',
            8 => 'Sub-licence arrangements (if any)?',
            9 => 'Project title*',
            10 => 'Lay summary*',
            11 => 'Public benefit statement*',
            12 => 'Request category type',
            13 => 'Technical summary',
            14 => 'Other approval committees',
            15 => 'Project start date',
            16 => 'Project end date',
            17 => 'Latest approval date*',
            18 => 'Dataset(s) name*',
            19 => 'Data sensitivity level',
            20 => 'Legal basis for provision of data under Article 6',
            21 => 'Lawful conditions for provision of data under Article 9',
            22 => 'Common Law Duty of Confidentiality',
            23 => 'National data opt-out applied?',
            24 => 'Request frequency',
            25 => 'For linked datasets, specify how the linkage will take place',
            26 => 'Description of the confidential data being used',
            27 => 'Release/Access date',
            28 => 'Access type*',
            29 => 'How has data been processed to enhance privacy?',
            30 => 'Link to research outputs',
        ];
    }

    private function validateRow(array $overrides = []): array
    {
        $row = [
            0 => 'PRJ=001',
            1 => 'Organisation name',
            2 => 'ORG-001',
            3 => 'NHS',
            4 => 'Applicant A, Applicant B',
            5 => 'APP-001',
            6 => 'Founder A, Founder B',
            7 => 'Yes',
            8 => 'No',
            9 => 'Project title',
            10 => 'Lay summary text',
            11 => 'Public benefit statement',
            12 => 'Category A',
            13 => 'Technical summary',
            14 => 'Committee A, Committee B',
            15 => 45000,
            16 => 46000,
            17 => 47000,
            18 => 'Dataset A, Dataset B',
            19 => 'Low',
            20 => 'Article 6 basis',
            21 => 'Article 9 basis',
            22 => 'Confidential',
            23 => 'Opted out',
            24 => 'One-off',
            25 => 'Linkage description',
            26 => 'Confidential data description',
            27 => 48000,
            28 => 'Open',
            29 => 'TRE',
            30 => 'Output A, Output B',
        ];

        foreach ($overrides as $key => $value) {
            $row[$key] = $value;
        }

        return $row;
    }

    private function runImport(ImportDurFile $import, array $dataRows): void
    {
        $import->model($this->validateHeaders());
        foreach ($dataRows as $row) {
            $import->model($row);
        }
    }

    // private function mockDurCreate(int $id): object
    // {
    //     $dur = new Dur();
    //     $dur->id = $id;

    //     $mock = Mockery::mock('alias:' . Dur::class);
    //     $mock->shouldReceive('create')->andReturn($dur);
    //     $mock->allows()->getAttribute('id')->andReturn($id);
    // }

    // import file configuration
    public function test_targets_the_data_uses_template_sheet(): void
    {
        $import = new ImportDurFile($this->data);

        $this->assertArrayHasKey('Data Uses Template', $import->sheets());
    }

    public function test_starts_on_row_2(): void
    {
        $import = new ImportDurFile($this->data);

        $this->assertEquals(2, $import->startRow());
    }

    // empty rows
    public function test_skips_empty_rows(): void
    {
        $import = new ImportDurFile($this->data, dryRun: true);
        $result = $import->model([]);

        $this->assertNull($result);
        $this->assertEmpty($import->errors);
    }

    public function test_skips_whitespace_only_rows(): void
    {
        $import = new ImportDurFile($this->data, dryRun: true);
        $result = $import->model(array_fill(0, 31, '     '));

        $this->assertNull($result);
        $this->assertEmpty($import->errors);
    }

    // header
    public function test_accepts_valid_headers(): void
    {
        $import = new ImportDurFile($this->data, dryRun: true);
        $import->model($this->validateHeaders());

        $this->assertEmpty($import->errors);
    }

    public function test_collects_errors_for_missing_header_column(): void
    {
        $import = new ImportDurFile($this->data, dryRun: true);
        $headers = $this->validateHeaders();
        $headers[1] = '';

        $import->model($headers);

        $fields = $import->errors[0]['field'];
        $this->assertEquals('Organisation name*', $fields);
    }

    public function test_collects_errors_for_wrong_header_column_name(): void
    {
        $import = new ImportDurFile($this->data, dryRun: true);
        $headers = $this->validateHeaders();
        $headers[9] = 'Wrong header';

        $import->model($headers);

        $fields = $import->errors[0]['field'];
        $this->assertEquals('Project title*', $fields);
    }

    public function it_reports_header_errors_on_row_2(): void
    {
        $import = new ImportDurFile($this->data, dryRun: true);
        $headers = $this->validateHeaders();
        $headers[1] = 'Wrong';

        $import->model($headers);

        $this->assertEquals(2, $import->errors[0]['row']);
    }

    public function it_collects_errors_for_multiple_wrong_headers(): void
    {
        $import = new ImportDurFile($this->data, dryRun: true);
        $headers = $this->validateHeaders();
        $headers[1] = 'Wrong A';
        $headers[9] = 'Wrong B';
        $headers[28] = 'Wrong C';

        $import->model($headers);

        $this->assertCount(3, $import->errors);
    }

    public function it_matches_headers_case_insensitively(): void
    {
        $import = new ImportDurFile($this->data, dryRun: true);
        $headers = $this->validateHeaders();
        $headers[0] = 'PROJECT ID';

        $import->model($headers);

        $this->assertEmpty($import->errors);
    }

    // dry run
    public function test_does_not_insert_in_dry_run_mode(): void
    {
        $import = new ImportDurFile($this->data, dryRun: true);
        $this->runImport($import, [$this->validateRow()]);

        $this->assertEmpty($import->durIds);
        $this->assertEmpty($import->errors);
    }

    // required fields
    public function test_collects_error_for_missing_organisation_name(): void
    {
        $import = new ImportDurFile($this->data, dryRun: true);
        $this->runImport($import, [$this->validateRow([1 => ''])]);

        $fields = $import->errors[0]['field'];
        $this->assertEquals('organisation name', $fields);
    }

    public function test_collects_error_for_missing_project_title(): void
    {
        $import = new ImportDurFile($this->data, dryRun: true);
        $this->runImport($import, [$this->validateRow([9 => ''])]);

        $fields = $import->errors[0]['field'];
        $this->assertEquals('project title', $fields);
    }

    public function test_collects_error_for_missing_lay_summary(): void
    {
        $import = new ImportDurFile($this->data, dryRun: true);
        $this->runImport($import, [$this->validateRow([10 => ''])]);

        $fields = $import->errors[0]['field'];
        $this->assertEquals('lay summary', $fields);
    }

    public function test_collects_error_for_missing_public_benefit_statement(): void
    {
        $import = new ImportDurFile($this->data, dryRun: true);
        $this->runImport($import, [$this->validateRow([11 => ''])]);

        $fields = $import->errors[0]['field'];
        $this->assertEquals('public benefit statement', $fields);
    }

    public function test_collects_error_for_missing_latest_approval_date(): void
    {
        $import = new ImportDurFile($this->data, dryRun: true);
        $this->runImport($import, [$this->validateRow([17 => ''])]);

        $fields = $import->errors[0]['field'];
        $this->assertEquals('latest approval date', $fields);
    }

    public function test_collects_error_for_missing_dataset_name(): void
    {
        $import = new ImportDurFile($this->data, dryRun: true);
        $this->runImport($import, [$this->validateRow([18 => ''])]);

        $fields = $import->errors[0]['field'];
        $this->assertEquals('dataset(s) name', $fields);
    }

    public function test_collects_error_for_missing_access_type(): void
    {
        $import = new ImportDurFile($this->data, dryRun: true);
        $this->runImport($import, [$this->validateRow([28 => ''])]);

        $fields = $import->errors[0]['field'];
        $this->assertEquals('access type', $fields);
    }

    public function test_collects_errors_for_multiple_required_fields(): void
    {
        $import = new ImportDurFile($this->data, dryRun: true);
        $this->runImport($import, [$this->validateRow([1 => '', 9 => '', 28 => ''])]);

        $fields = $import->errors[0]['field'];
        $this->assertEquals('organisation name', $fields);
        $fields = $import->errors[1]['field'];
        $this->assertEquals('project title', $fields);
        $fields = $import->errors[2]['field'];
        $this->assertEquals('access type', $fields);
    }

    // date validation
    public function test_collects_error_for_invalid_latest_approval_date(): void
    {
        $import = new ImportDurFile($this->data, dryRun: true);
        $this->runImport($import, [$this->validateRow([17 => 'not-a-date'])]);

        $fields = $import->errors[0]['field'];
        $this->assertEquals('latest approval date', $fields);
    }

    // row numnbers
    public function test_tracks_row_number_correctly_across_multiple_rows(): void
    {
        $import = new ImportDurFile($this->data, dryRun: true);
        $this->runImport($import, [
            $this->validateRow([1 => '   ']),
            $this->validateRow([9 => '   ']),
        ]);

        $row = $import->errors[0]['row'];
        $this->assertEquals(3, $row);
        $row = $import->errors[1]['row'];
        $this->assertEquals(4, $row);
    }

    public function test_includes_row_numnber_in_error_message(): void
    {
        $import = new ImportDurFile($this->data, dryRun: true);
        $this->runImport($import, [$this->validateRow([1 => '   '])]);

        $message = $import->errors[0]['message'];
        $this->assertStringContainsString('Row 3', $message);
    }
}
