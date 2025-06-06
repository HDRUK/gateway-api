<?php

namespace App\Http\Requests\Team;

use App\Models\Team;
use Illuminate\Validation\Rule;
use App\Http\Enums\TeamMemberOf;
use App\Http\Requests\BaseFormRequest;

class EditTeam extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'teamId' => [
                'int',
                'required',
                // 'exists:teams,teamId',
                function ($attribute, $value, $fail) {
                    $exists = Team::where('id', $value)->count();

                    if (!$exists) {
                        $fail('The selected team not exist.');
                    }
                },
            ],
            'name' => [
                'string',
            ],
            'enabled' => [
                'boolean',
            ],
            'allows_messaging' => [
                'boolean',
            ],
            'workflow_enabled' => [
                'boolean',
            ],
            'access_requests_management' => [
                'boolean',
            ],
            'uses_5_safes' => [
                'boolean',
            ],
            'is_admin' => [
                'boolean',
            ],
            'member_of' => [
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
            'dar_modal_content' => [
                'nullable',
                'string',
            ],
            'service' => [
                'nullable',
                'regex:/^(https?:\/\/[^\s,]+(,[^\s,]+)*)?$/i',
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
        $this->merge(['teamId' => $this->route('teamId')]);
    }
}
