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
                ]),
            ],
            'contact_point' => [
                'string',
            ],
            'application_form_updated_by' => [
                'string',
            ],
            'application_form_updated_on' => [
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
