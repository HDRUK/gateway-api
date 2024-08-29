<?php

namespace App\Http\Requests\AuditLog;

use Config;
use App\Http\Requests\BaseFormRequest;

class UpdateAuditLog extends BaseFormRequest
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
                'required',
                'exists:audit_logs,id',
            ],
            'user_id' => [
                'integer',
                'exists:users,id',
            ],
            'team_id' => [
                'integer',
                'exists:teams,id',
            ],
            'action_type' => [
                'string',
                'max:50',
            ],
            'action_name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'required',
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

    /**
     * Provides informational messages based on the invalid request
     * parameters.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => Config::get('strings.required'),
            'description.required' => Config::get('strings.required'),
            'function.required' => Config::get('strings.required'),

            'description.string' => Config::get('strings.string'),
            'function.string' => Config::get('strings.string'),

            'user_id.numeric' => Config::get('strings.numeric'),

            'description.max' => Config::get('strings.max'),
            'function.max' => Config::get('strings.max'),

            'user_id.exists'  => Config::get('strings.exists'),
        ];
    }
}
