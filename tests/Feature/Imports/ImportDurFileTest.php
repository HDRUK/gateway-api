<?php

namespace Tests\Feature\Imports;

// use Mockery;

use App\Imports\ImportDurFile;
use Tests\TestCase;

class ImportDurFileTest extends TestCase
{
    private array $data = [
        'user_id' => 1,
        'team_id' => 1,
    ];

    private function validateHeaders(): array
    {
        return [
            '0' => 'Project ID',
            '1' => 'Organisation name*',
            '2' => 'Organisation ID',
            '3' => 'Organisation sector',
            '4' => 'Applicant name(s)',
            '5' => 'Applicant ID',
            '6' => 'Funders/ Sponsors',
            '7' => 'DEA accredited researcher?',
            '8' => 'Sub-licence arrangements (if any)?',
            '9' => 'Project title*',
            '10' => 'Lay summary*',
            '11' => 'Public benefit statement*',
            '12' => 'Request category type',
            '13' => 'Technical summary',
            '14' => 'Other approval committees',
            '15' => 'Project start date',
            '16' => 'Project end date',
            '17' => 'Latest approval date*',
            '18' => 'Dataset(s) name*',
            '19' => 'Data sensitivity level',
            '20' => 'Legal basis for provision of data under Article 6',
            '21' => 'Lawful conditions for provision of data under Article 9',
            '22' => 'Common Law Duty of Confidentiality',
            '23' => 'National data opt-out applied?',
            '24' => 'Request frequency',
            '25' => 'For linked datasets, specify how the linkage will take place',
            '26' => 'Description of the confidential data being used',
            '27' => 'Release/Access date',
            '28' => 'Access type*',
            '29' => 'How has data been processed to enhance privacy?',
            '30' => 'Link to research outputs',
        ];
    }

    private function validateRow(array $overrides = []): array
    {
        $row = [
            '0' => 'PRJ=001',
            '1' => 'Organisation name',
            '2' => 'ORG-001',
            '3' => 'NHS',
            '4' => 'Applicant A, Applicant B',
            '5' => 'APP-001',
            '6' => 'Founder A, Founder B',
            '7' => 'Yes',
            '8' => 'No',
            '9' => 'Project title',
            '10' => 'Lay summary text',
            '11' => 'Public benefit statement',
            '12' => 'Category A',
            '13' => 'Technical summary',
            '14' => 'Committee A, Committee B',
            '15' => 45000,
            '16' => 46000,
            '17' => 47000,
            '18' => 'Dataset A, Dataset B',
            '19' => 'Low',
            '20' => 'Article 6 basis',
            '21' => 'Article 9 basis',
            '22' => 'Confidential',
            '23' => 'Opted out',
            '24' => 'One-off',
            '25' => 'Linkage description',
            '26' => 'Confidential data description',
            '27' => 48000,
            '28' => 'Open',
            '29' => 'TRE',
            '30' => 'Output A, Output B',
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

}
