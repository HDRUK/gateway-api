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
                'boolean',
            ],
            'member_of' => [
                'required',
                'string',
                Rule::in([
                    TeamMemberOf::ALLIANCE,
                    TeamMemberOf::HUB,
                    TeamMemberOf::OTHER,
                    TeamMemberOf::NCS,
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
                'nullable',
                'string',
            ],
            'notifications' => [
                'nullable',
                'array',

            ],
            'mongo_object_id' => [
                'nullable',
                'string',
            ],
            'is_question_bank' => [
                'boolean',
            ],
            'users' => [
                'array',
            ],
            'users.*'  => [
                'integer',
                'distinct',
                'exists:users,id',
            ],
            'is_provider' => [
                'boolean',
            ],
            'url' => [
                'nullable',
                'url:http,https',
            ],
            'introduction' => [
                'nullable', // this is because we have no information at the moment and this information does not exist in mk1 upon migration
                'string',
            ],
            'dar_modal_header' => [
                'nullable',
                'string',
            ],
            'dar_modal_content' => [
                'nullable',
                'string',
            ],
            'dar_modal_footer' => [
                'nullable',
                'string',
            ],
            'service' => [
                'nullable',
                'regex:/^(https?:\/\/[^\s,]+(,[^\s,]+)*)?$/i',
            ],
        ];
    }
}
