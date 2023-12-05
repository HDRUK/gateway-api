<?php

namespace App\Http\Requests\Team;

use App\Http\Enums\TeamMemberOf;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

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
                'string',
                Rule::in([
                    TeamMemberOf::ALLIANCE,
                    TeamMemberOf::HUB,
                    TeamMemberOf::OTHER,
                ]),
            ],
            'contact_point' => [
                'nullable',
                'string',
            ],
            'application_form_updated_by' => [
                'nullable',
                'string',
            ],
            'application_form_updated_on' => [
                'required',
                'string',
            ],
            'notifications' => [
                'array',

            ],
            'mdm_folder_id' => [
                'string',
            ],
            'mongo_object_id' => [
                'nullable',
                'string',
            ]
        ];
    }
}
