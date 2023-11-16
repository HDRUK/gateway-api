<?php

namespace App\Http\Requests\TeamNotification;

use App\Http\Requests\BaseFormRequest;

class CreateTeamNotification extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'teamId' => [
                'int',
                'required',
                'exists:teams,id',
            ],
            'user_notification_status' => [
                'required',
                'boolean',
            ],
            'team_notification_status' => [
                'required',
                'boolean',
            ],
            'team_emails' => [
                'present',
                'array',
            ],
            'team_emails.*' => [
                'required',
                'string',
                'email',
                'distinct',
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
