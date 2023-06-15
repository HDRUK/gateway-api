<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;

class UpdateDataUseRegisterRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'counter' => [
                'int',
                'required',
            ],
            'keywords' => [
                'array',
            ],
            'dataset_ids' => [
                'array',
                'required',
            ],
            'gateway_dataset_ids' => [
                'array',
                'required',
            ],
            'non_gateway_dataset_ids' => [
                'array',
            ],
            'gateway_applicants' => [
                'array',
            ],
            'non_gateway_applicants' => [
                'array',
            ],
            'funders_and_sponsors' => [
                'array',
            ],
            'other_approval_committees' => [
                'array',
            ],
            'gateway_output_tools' => [
                'array',
            ],
            'gateway_output_papers' => [
                'array',
            ],
            'non_gateway_outputs' => [
                'array',
            ],
            'project_title' => [
                'string',
                'required',
            ],
            'project_id_text' => [
                'string',
                'required',
            ],
            'organisation_name' => [
                'string',
                'required',
            ],
            'organisation_sector' => [
                'string',
                'required',
            ],
            'lay_summary' => [
                'string',
            ],
            //
            'user_id' => [
                'int',
                'required',
                'exists:users,id',
            ],
            'team_id' => [
                'int',
                'required',
                'exists:teams,id',
            ],
            'rejection_reason' => [
                'string',
            ],
        ];
    }
}
