<?php

namespace App\Imports;

use Throwable;
use CloudLogger;
use Carbon\Carbon;
use App\Models\Dur;
use App\Http\Traits\MapOrganisationSector;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStartRow;

class ImportDurFile implements WithMultipleSheets, ToModel, WithStartRow, SkipsOnError
{
    use MapOrganisationSector;

    private array $data;
    private int $currentRow;
    private bool $dryRun;

    public array $durIds = [];
    public array $errors = [];

    private array $expectedHeaders = [
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

    public function __construct(array $data = [], bool $dryRun = false)
    {
        $this->data = $data;
        $this->currentRow = $this->startRow();
        $this->dryRun = $dryRun;
    }

    public function sheets(): array
    {
        return [
            'Data Uses Template' => $this,
        ];
    }

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }

    public function onError(Throwable $e)
    {
        $rowNumber = $this->currentRow++;

        $this->errors[] = [
            'row' => $rowNumber,
            'field' => null,
            'message' => $e->getMessage(),
        ];
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $rowNumber = $this->currentRow++;

        if (empty($row) || empty(array_filter(array_map('trim', array_map('strval', $row))))) {
            return null;
        }

        // validate header
        if ($rowNumber === 2) {
            $this->validateHeaders($row);

            return null;
        }


        $rowErrors = $this->validateRow($row, $rowNumber);

        if (!empty($rowErrors)) {
            foreach ($rowErrors as $error) {
                $this->errors[] = $error;
            }

            // skip current row for insert
            return null;
        }

        if ($this->dryRun) {
            return null;
        }

        $dur = Dur::create([
            'project_id_text' => $row[0],
            'organisation_name' => $row[1],
            'organisation_id' => $row[2],
            'organisation_sector' => $row[3] ?? null,
            'non_gateway_applicants' => explode(",", $row[4]),
            'applicant_id' => $row[5],
            'funders_and_sponsors' => explode(",", $row[6]),
            'accredited_researcher_status' => $row[7],
            'sublicence_arrangements' => $row[8],
            'project_title' => $row[9],
            'lay_summary' => $row[10],
            'public_benefit_statement' => $row[11],
            'request_category_type' => $row[12],
            'technical_summary' => $row[13],
            'other_approval_committees' => explode(",", $row[14]),
            'project_start_date' => $row[15] ? $this->calculateExcelDate('Project Start Date', $row[15]) : null,
            'project_end_date' =>  $row[16] ? $this->calculateExcelDate('Project End Date', $row[16]) : null,
            'latest_approval_date' => $row[17] ? $this->calculateExcelDate('Latest Approval Date', $row[17]) : null,
            'non_gateway_datasets' => array_values(array_filter(array_map('trim', explode(',', $row[18])))),
            'data_sensitivity_level' => $row[19],
            'legal_basis_for_data_article6' => $row[20],
            'legal_basis_for_data_article9' => $row[21],
            'duty_of_confidentiality' => $row[22],
            'national_data_optout' => $row[23],
            'request_frequency' => $row[24],
            'confidential_data_description' => $row[26],
            'access_date' => $row[27] ? $this->calculateExcelDate('Access Date', $row[27]) : null,
            'access_type' => $row[28],
            'privacy_enhancements' => $row[29],
            'non_gateway_outputs' => explode(",", $row[30]), // ??? non or gateway research outputs ???
            'status' => 'DRAFT',
            'enabled' => true,
            'user_id' => $this->data['user_id'],
            'team_id' => $this->data['team_id'],
            'sector_id' => $row[3] ? $this->mapOrganisationSector($row[3]) : null,
            'dataset_linkage_description' => $row[25] ?? null,
        ]);

        $this->durIds[] = (int) $dur->id;

        return $dur;
    }

    private function validateHeaders(array $row)
    {
        foreach ($this->expectedHeaders as $idx => $expectedName) {
            $actual = isset($row[$idx]) ? trim((string) $row[$idx]) : null;

            if ($actual === null || $actual === '') {
                $this->errors[] = [
                    'row' => 2,
                    'field' => $expectedName,
                    'message' => "Header row: column {$idx} is missing, expected \"{$expectedName}\".",
                ];
            } elseif (strtolower($actual) !== strtolower($expectedName)) {
                $this->errors[] = [
                    'row' => 2,
                    'field' => $expectedName,
                    'message' => "Header row: column {$idx} is \"{$actual}\", expected \"{$expectedName}\".",
                ];
            }
        }
    }

    private function validateRow(array $row, int $rowNumber): array
    {
        $errors = [];

        $required = [
            1 => 'organisation name',
            9 => 'project title',
            10 => 'lay summary',
            11 => 'public benefit statement',
            17 => 'latest approval date',
            18 => 'dataset(s) name',
            28 => 'access type',
        ];

        foreach ($required as $idx => $value) {
            if (is_null($row[$idx]) || trim($row[$idx]) === '' || strlen(trim($row[$idx])) === 0) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'field' => $value,
                    'message' => "Row {$rowNumber}: missing required field: {$value}",
                ];

                CloudLogger::write([
                    'action_type' => 'Data use import',
                    'action_name' => class_basename($this) . '@model',
                    'description' => 'Row ' . $rowNumber . ': missing required field: ' . $value,
                ], 'WARNING');
            }
        }

        if (isset($row[17]) && trim((string) $row[17]) !== '') {
            try {
                $date = $this-> calculateExcelDate('Latest Approval Date', $row[17]);

                if (!$date || !Carbon::hasFormat($date, 'Y-m-d')) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'field' => 'latest approval date',
                        'message' => "Row {$rowNumber}: invalid date format for latest approval date: {$row[17]}",
                    ];

                    CloudLogger::write([
                        'action_type' => 'Data use import',
                        'action_name' => class_basename($this) . '@model',
                        'description' => 'Row ' . $rowNumber . ': : invalid date format for latest approval date: ' . $row[17],
                    ], 'WARNING');
                }
            } catch (Throwable $e) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'field' => 'latest approval date',
                    'message' => "Row {$rowNumber}: unable to parse latest approval date: {$row[17]}",
                ];

                CloudLogger::write([
                    'action_type' => 'Data use import',
                    'action_name' => class_basename($this) . '@model',
                    'description' => 'Row ' . $rowNumber . ': unable to parse latest approval date: ' . $row[17],
                ], 'WARNING');
            }
        }


        return $errors;
    }

    // $excelDate is Excel serial date
    private function calculateExcelDate(string $name, int $excelDate)
    {

        try {
            $latestApprovalDate = Carbon::createFromTimestampUTC(($excelDate - 25569) * 86400)->toDateString();

            if (!Carbon::hasFormat($latestApprovalDate, 'Y-m-d')) {
                CloudLogger::write([
                    'action_type' => 'Data use import',
                    'action_name' => class_basename($this) . '@model',
                    'description' => $name . ' Invalid date format',
                    'data' => $excelDate,
                ], 'WARNING');
                return null;
            }

            return $latestApprovalDate;
        } catch (Throwable $e) {
            CloudLogger::write([
                'action_type' => 'Data use import',
                'action_name' => class_basename($this) . '@model',
                'description' =>  $name . ' Invalid date format',
                'data' => $excelDate,
            ], 'WARNING');
            return null;
        }
    }
}
