<?php

namespace App\Http\Requests\V2\Dur;

use App\Http\Requests\BaseFormRequest;

class EditDurByTeamAndId extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'teamId' => 'required|int|exists:teams,id',
            'id' => 'required|int|exists:dur,id',
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
                'nullable',
                'string',
            ],
            'project_id_text' => [
                'nullable',
                'string',
            ],
            'organisation_name' => [
                'nullable',
                'string',
            ],
            'organisation_sector' => [
                'nullable',
                'string',
            ],
            'lay_summary' => [
                'nullable',
                'string',
            ],
            'technical_summary' => [
                'nullable',
                'string',
            ],
            'latest_approval_date' => [
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'manual_upload' => [
                'boolean',
            ],
            'rejection_reason' => [
                'nullable',
                'string',
            ],
            'sublicence_arrangements' => [
                'nullable',
                'string',
            ],
            'public_benefit_statement' => [
                'nullable',
                'string',
            ],
            'data_sensitivity_level' => [
                'nullable',
                'string',
            ],
            'project_start_date' => [
                'nullable',
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'project_end_date' => [
                'nullable',
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'access_date' => [
                'nullable',
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'accredited_researcher_status' => [
                'nullable',
                'string',
            ],
            'confidential_data_description' => [
                'nullable',
                'string',
            ],
            'dataset_linkage_description' => [
                'nullable',
                'string',
            ],
            'duty_of_confidentiality' => [
                'nullable',
                'string',
            ],
            'legal_basis_for_data_article6' => [
                'nullable',
                'string',
            ],
            'legal_basis_for_data_article9' => [
                'nullable',
                'string',
            ],
            'national_data_optout' => [
                'nullable',
                'string',
            ],
            'organisation_id' => [
                'nullable',
                'string',
            ],
            'privacy_enhancements' => [
                'nullable',
                'string',
            ],
            'request_category_type' => [
                'nullable',
                'string',
            ],
            'request_frequency' => [
                'nullable',
                'string',
            ],
            'access_type' => [
                'nullable',
                'string',
            ],
            'mongo_object_dar_id' => [
                'nullable',
                'string',
            ],
            'user_id' => [
                'integer',
                'exists:users,id',
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
            'applicant_id' => [
                'nullable',
                'string',
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
            'datasets.*.id'  => [
                'integer',
                'exists:datasets,id',
            ],
            'datasets.*.is_locked'  => [
                'boolean',
            ],
            'datasets.*.updated_at'  => [
                'nullable',
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'datasets.*.user_id'  => [
                'integer',
                'exists:users,id',
            ],
            'datasets.*.reason'  => [
                'nullable',
                'string',
            ],
            'publications' => [
                'array',
            ],
            'publications.*.id'  => [
                'integer',
                'exists:publications,id',
            ],
            'publications.*.updated_at'  => [
                'nullable',
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'tools' => [
                'array',
            ],
            'tools.*' => [
                'integer',
                'distinct',
            ],
            'created_at' => [
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'updated_at' => [
                'date_format:Y-m-d\TH:i:s', // 2017-09-12T00:00:00
            ],
            'status' => [
                'string',
                'in:ACTIVE,ARCHIVED,DRAFT',
            ],
        ];
    }

    /**
     * Add Route parameters to the FormRequest.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'teamId' => $this->route('teamId'),
            'id' => $this->route('id'),
        ]);
    }
}
