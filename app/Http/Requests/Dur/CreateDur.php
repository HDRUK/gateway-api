<?php

namespace App\Http\Requests\Dur;

use App\Http\Requests\BaseFormRequest;

class CreateDur extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'non_gateway_datasets' => [
                'array',
            ],
            'non_gateway_datasets.*' => [
                'string',
            ],
            'non_gateway_applicants' => [
                'array',
            ],
            'non_gateway_applicants.*' => [
                'string',
            ],
            'funders_and_sponsors' => [
                'array',
            ],
            'funders_and_sponsors.*' => [
                'string',
            ],
            'other_approval_committees' => [
                'array',
            ],
            'other_approval_committees.*' => [
                'string',
            ],
            'gateway_outputs_tools' => [
                'array',
            ],
            'gateway_outputs_tools.*' => [
                'string',
            ],
            'gateway_outputs_papers' => [
                'array',
            ],
            'gateway_outputs_papers.*' => [
                'string',
            ],
            'non_gateway_outputs' => [
                'array',
            ],
            'non_gateway_outputs.*' => [
                'string',
            ],
            'project_title' => [
                'string',
            ],
            'project_id_text' => [
                'string',
            ],
            'organisation_name' => [
                'string',
            ],
            'organisation_sector' => [
                'string',
            ],
            'lay_summary' => [
                'string',
            ],
            'technical_summary' => [
                'string',
            ],
            'latest_approval_date' => [
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'manual_upload' => [
                'boolean',
            ],
            'rejection_reason' => [
                'string',
            ],
            'sublicence_arrangements' => [
                'string',
            ],
            'public_benefit_statement' => [
                'string',
            ],
            'data_sensitivity_level' => [
                'string',
            ],
            'project_start_date' => [
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'project_end_date' => [
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'access_date' => [
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'accredited_researcher_status' => [
                'string',
            ],
            'confidential_description' => [
                'string',
            ],
            'dataset_linkage_description' => [
                'string',
            ],
            'duty_of_confidentiality' => [
                'string',
            ],
            'legal_basis_for_data_article6' => [
                'string',
            ],
            'legal_basis_for_data_article9' => [
                'string',
            ],
            'national_data_optout' => [
                'string',
            ],
            'organisation_id' => [
                'string',
            ],
            'privacy_enhancements' => [
                'string',
            ],
            'request_category_type' => [
                'string',
            ],
            'request_frequency' => [
                'string',
            ],
            'access_type' => [
                'string',
            ],
            'mongo_object_dar_id' => [
                'string',
            ],
            'user_id' => [
                'integer',
                'exists:users,id',
            ],
            'team_id' => [
                'integer',
                'exists:teams,id',
            ],
            'enabled' => [
                'boolean',
            ],
            'last_activity' => [
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'counter' => [
                'integer',
            ],
            'mongo_object_id' => [
                'nullable', 
                'string',
            ],
            'mongo_id' => [
                'nullable',
                'string',
            ],
            'keywords' => [
                'array',
            ],
            'keywords.*' => [
                'string',
                'distinct',
            ],
            'datasets' => [
                'array',
            ],
            'datasets.*'  => [
                'integer',
                'distinct',
                'exists:datasets,id',
            ],
            'createdAt' => [
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'updatedAt' => [
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
        ];
    }
}
