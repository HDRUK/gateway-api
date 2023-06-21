<?php

namespace App\Http\Requests;

use Config;
use App\Http\Requests\BaseFormRequest;

class EditAuditLog extends BaseFormRequest
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
            'description' => [
                'string',
                'max:1024',
            ],
            'function' => [
                'string',
                'max:128',
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
            'description.string' => Config::get('strings.string'),
            'function.string' => Config::get('strings.string'),

            'user_id.numeric' => Config::get('strings.numeric'),

            'description.max' => Config::get('strings.max'),
            'function.max' => Config::get('strings.max'),

            'user_id.exists'  => Config::get('strings.exists'),
        ];
    }
}
