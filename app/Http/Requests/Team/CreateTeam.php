<?php

namespace App\Http\Requests\Team;

use App\Http\Requests\BaseFormRequest;

class CreateTeam extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
            'allows_messaging' => [
                'required',
                'boolean',
            ],
            'workflow_enabled' => [
                'required',
                'boolean',
            ],
            'access_requests_management' => [
                'required',
                'boolean',
            ],
            'uses_5_safes' => [
                'required',
                'boolean',
            ],
            'is_admin' => [
                'required',
                'boolean',
            ],
            'member_of' => [
                'required',
                'integer',
            ],
            'contact_point' => [
                'required',
                'string',
            ],
            'application_form_updated_by' => [
                'required',
                'string',
            ],
            'application_form_updated_on' => [
                'required',
                'string',
            ],
            'notifications' => [
                'required',
                'array',

            ],
            'mdm_folder_id' => [
                'string',
            ]
        ];
    }
}
