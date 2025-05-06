<?php

namespace App\Imports;

use Throwable;
use CloudLogger;
use Carbon\Carbon;
use App\Models\Dur;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Http\Traits\MapOrganisationSector;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class DataUsesTemplateImport implements ToModel, WithStartRow, WithValidation
{
    use MapOrganisationSector;

    private $data;
    public array $durIds;

    public function __construct(array $data = [])
    {
        $this->data = $data;
        $this->durIds = [];
    }

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 3;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        if (trim($row[1]) === '') { // Check if the organisation name is empty
            CloudLogger::write([
                'action_type' => 'Data use import',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Data use import :: missing organisation name',
                'data' => $row,
            ], 'WARNING');
            return null;
        }

        if (trim($row[9]) === '') { // Check if the project title is empty
            CloudLogger::write([
                'action_type' => 'Data use import',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Data use import :: missing project title',
            ], 'WARNING');
            return null;
        }

        if (trim($row[10]) === '') { // Check if the lay summary is empty
            CloudLogger::write([
                'action_type' => 'Data use import',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Data use import :: missing lay summary',
            ], 'WARNING');
            return null;
        }

        if (trim($row[11]) === '') { // Check if the public benefit statement is empty
            CloudLogger::write([
                'action_type' => 'Data use import',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Data use import :: missing public benefit statement',
            ], 'WARNING');
            return null;
        }

        if (trim($row[17]) === '') { // Check if the latest approval date is empty
            CloudLogger::write([
                'action_type' => 'Data use import',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Data use import :: missing latest approval date',
            ], 'WARNING');
            return null;
        }

        if (trim($row[18]) === '') { // Check if the dataset(s) name is empty
            CloudLogger::write([
                'action_type' => 'Data use import',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Data use import :: missing dataset(s) name',
            ], 'WARNING');
            return null;
        }

        if (trim($row[28]) === '') { // Check if the access type is empty
            CloudLogger::write([
                'action_type' => 'Data use import',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Data use import :: missing access type',
            ], 'WARNING');
            return null;
        }

        try {
            $latestApprovalDate = $this->calculateExcelDate('Latest Approval Date', $row[17]);

            if (!Carbon::hasFormat($latestApprovalDate, 'Y-m-d')) {
                CloudLogger::write([
                    'action_type' => 'Data use import',
                    'action_name' => class_basename($this) . '@model',
                    'description' => 'Skipping row due to invalid latest approval date format',
                    'data' => $row[17],
                ], 'WARNING');
                return null;
            }
        } catch (Throwable $e) {
            CloudLogger::write([
                'action_type' => 'Data use import',
                'action_name' => class_basename($this) . '@model',
                'description' => 'Skipping row due to exception in date parsing',
                'data' => $row[17],
            ], 'WARNING');
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
            'non_gateway_datasets' => array_filter(array_map('trim', explode(',', $row[18]))), // ??? Gateway datasets or not ???
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

    // $excelDate is Excel serial date
    private function calculateExcelDate(string $name, int $excelDate)
    {

        try {
            $latestApprovalDate = Carbon::createFromTimestamp(($excelDate - 25569) * 86400)->toDateString();

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

    public function rules(): array
    {
        return [
             // organisation name
            '1' => [
                function ($attribute, $value, $fail) {
                    if (is_null($value) || trim($value) === '' || strlen(trim($value)) === 0) {
                        CloudLogger::write([
                            'action_type' => 'Data use import',
                            'action_name' => class_basename($this) . '@'.__FUNCTION__,
                            'description' => 'Data use import :: missing organisation name',
                        ], 'WARNING');
                        return;
                    }
                },
            ],
            // project title
            '9' => [
                function ($attribute, $value, $fail) {
                    if (is_null($value) || trim($value) === '' || strlen(trim($value)) === 0) {
                        CloudLogger::write([
                            'action_type' => 'Data use import',
                            'action_name' => class_basename($this) . '@'.__FUNCTION__,
                            'description' => 'Data use import :: missing project title',
                        ], 'WARNING');
                        return;
                    }
                },
            ],
            // lay summary
            '10' => [
                function ($attribute, $value, $fail) {
                    if (is_null($value) || trim($value) === '' || strlen(trim($value)) === 0) {
                        CloudLogger::write([
                            'action_type' => 'Data use import',
                            'action_name' => class_basename($this) . '@'.__FUNCTION__,
                            'description' => 'Data use import :: missing lay summary',
                        ], 'WARNING');
                        return;
                    }
                },
            ],
            // public benefit statement
            '11' => [
                function ($attribute, $value, $fail) {
                    if (is_null($value) || trim($value) === '' || strlen(trim($value)) === 0) {
                        CloudLogger::write([
                            'action_type' => 'Data use import',
                            'action_name' => class_basename($this) . '@'.__FUNCTION__,
                            'description' => 'Data use import :: missing public benefit statement',
                        ], 'WARNING');
                        return;
                    }
                },
            ],
            // latest approval date
            '17' => [
                function ($attribute, $value, $fail) {
                    if (is_null($value) || trim($value) === '' || strlen(trim($value)) === 0) {
                        CloudLogger::write([
                            'action_type' => 'Data use import',
                            'action_name' => class_basename($this) . '@'.__FUNCTION__,
                            'description' => 'Data use import :: missing latest approval date',
                        ], 'WARNING');
                        return;
                    }

                    try {
                        $date = $this->calculateExcelDate('Latest approval date', $value);

                        if (!Carbon::hasFormat($date, 'Y-m-d')) {
                            CloudLogger::write([
                                'action_type' => 'Data use import',
                                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                                'description' => 'Data use import :: latest approval date has invalid format',
                                'data' => $date,
                            ], 'WARNING');

                            return;
                        }
                    } catch (\Throwable $e) {
                        CloudLogger::write([
                            'action_type' => 'Data use import',
                            'action_name' => class_basename($this) . '@' . __FUNCTION__,
                            'description' => 'Data use import :: unable to parse latest approval date',
                            'data' => $value,
                        ], 'WARNING');

                        return;
                    }
                },
            ],
            // dataset(s) name
            '18' => [
                function ($attribute, $value, $fail) {
                    if (is_null($value) || trim($value) === '' || strlen(trim($value)) === 0) {
                        CloudLogger::write([
                            'action_type' => 'Data use import',
                            'action_name' => class_basename($this) . '@'.__FUNCTION__,
                            'description' => 'Data use import :: missing dataset(s) name',
                        ], 'WARNING');
                        return;
                    }
                },
            ],
            // access type
            '28' => [
                function ($attribute, $value, $fail) {
                    if (is_null($value) || trim($value) === '' || strlen(trim($value)) === 0) {
                        CloudLogger::write([
                            'action_type' => 'Data use import',
                            'action_name' => class_basename($this) . '@'.__FUNCTION__,
                            'description' => 'Data use import :: missing access type',
                        ], 'WARNING');
                        return;
                    }
                },
            ],
        ];
    }
}
