<?php

namespace App\Imports;

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
        if (trim($row[0]) === '') {
            return null;
        }

        $dur = Dur::create([
            'project_id_text' => $row[0],
            'organisation_name' => $row[1],
            'organisation_id' => $row[2],
            'organisation_sector' => $row[3] ? $this->mapOrganisationSector($row[3]) : null,
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
            'project_start_date' => $row[15] ? $this->calculateExcelDate($row[15]) : null,
            'project_end_date' =>  $row[16] ? $this->calculateExcelDate($row[16]) : null,
            'latest_approval_date' => $row[17] ? $this->calculateExcelDate($row[17]) : null,
            'non_gateway_datasets' => explode(",", $row[18]), // ??? Gateway datasets or not ???
            'data_sensitivity_level' => $row[19],
            'legal_basis_for_data_article6' => $row[20],
            'legal_basis_for_data_article9' => $row[21],
            'duty_of_confidentiality' => $row[22],
            'national_data_optout' => $row[23],
            'request_frequency' => $row[24],
            'confidential_data_description' => $row[26],
            'access_date' => $row[27] ? $this->calculateExcelDate($row[27]) : null,
            'access_type' => $row[28],
            'privacy_enhancements' => $row[29],
            'non_gateway_outputs' => explode(",", $row[30]), // ??? non or gateway research outputs ???
            'status' => 'DRAFT',
            'enabled' => true,
            'user_id' => $this->data['user_id'],
            'team_id' => $this->data['team_id'],
        ]);

        $this->durIds[] = (int) $dur->id;

        return $dur;
    }

    // $excelDate is Excel serial date
    private function calculateExcelDate(int $excelDate)
    {
        return Carbon::createFromTimestamp(($excelDate - 25569) * 86400)->toDateString();
    }

    public function rules(): array
    {
        return [
            '0' => [
                function ($attribute, $value, $fail) {
                    // Skip validation if the cell is empty
                    if (is_null($value) || trim($value) === '' || strlen(trim($value)) === 0) {
                        return;
                    }
                },
            ]
        ];
    }
}
