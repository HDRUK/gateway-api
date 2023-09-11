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
            'notification_type' => [
                'required',
                'string',
            ],
            'message' => [
                'required',
                'string',
            ],
            'opt_in' => [
                'required',
                'boolean',
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
            'email' => [
                'required',
                'email',
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
