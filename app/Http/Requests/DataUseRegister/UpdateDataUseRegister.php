<?php

namespace App\Http\Requests\DataUseRegister;

use App\Models\TeamHasUser;
use App\Http\Requests\BaseFormRequest;

class UpdateDataUseRegister extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'id' => [
                'int',
                'required',
                'exists:data_use_registers,id',
            ],
            'counter' => [
                'required',
                'int',
            ],
            'keywords' => [
                'nullable',
                'array',
            ],
            'dataset_ids' => [
                'required',
                'array',
            ],
            'gateway_dataset_ids' => [
                'required',
                'array',
            ],
            'non_gateway_dataset_ids' => [
                'nullable',
                'array',
            ],
            'gateway_applicants' => [
                'nullable',
                'array',
            ],
            'non_gateway_applicants' => [
                'nullable',
                'array',
            ],
            'funders_and_sponsors' => [
                'nullable',
                'array',
            ],
            'other_approval_committees' => [
                'nullable',
                'array',
            ],
            'gateway_output_tools' => [
                'nullable',
                'array',
            ],
            'gateway_output_papers' => [
                'nullable',
                'array',
            ],
            'non_gateway_outputs' => [
                'nullable',
                'array',
            ],
            'project_title' => [
                'required',
                'string',
            ],
            'project_id_text' => [
                'required',
                'string',
            ],
            'organisation_name' => [
                'required',
                'string',
            ],
            'organisation_sector' => [
                'required',
                'string',
            ],
            'lay_summary' => [
                'nullable',
                'string',
            ],
            'latest_approval_date' => [
                'nullable',
                'date_format:Y-m-d H:i:s',
            ],
            'enabled' => [
                'nullable',
                'boolean',
            ],
            'team_id' => [
                'required',
                'int',
                'exists:teams,id',
            ],
            'user_id' => [
                'required',
                'int',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $exists = TeamHasUser::where('user_id', $value)
                        ->where('team_id', $this->team_id)
                        ->exists();

                    if (!$exists) {
                        $fail('The selected user is not a member of the specified team.');
                    }
                },
            ],
            'manual_upload' => [
                'nullable',
                'boolean',
            ],
            'last_activity' => [
                'nullable',
                'date_format:Y-m-d H:i:s',
            ],
            'rejection_reason' => [
                'nullable',
                'string',
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
        $this->merge(['id' => $this->route('id')]);
    }
}
